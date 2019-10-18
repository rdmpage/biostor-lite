//----------------------------------------------------------------------------------------
function to_csl(doc) {
    var csl = {};
    
 
 	switch (doc.type) {
 		case 'article':
 			csl.type = 'article-journal';
 			break;
 	
 		default:
 			csl.type = doc.type;
 			break;
 	}
 	
	
	// title
	if (doc.title) {
		csl.title = doc.title;
	}
 	
 
 	// date
 	if (doc.date) {
 		csl.issued = {};
		csl.issued['date-parts'] = []
		csl.issued['date-parts'].push(doc.date);		
 	} else {
		if (doc.year) {
			csl.issued = {};
			csl.issued['date-parts'] = [];
			csl.issued['date-parts'].push([parseInt(doc.year)]);
		}
	} 
	
	if (doc.author) {
		csl.author = [];
		
		for (var i in doc.author) {
			var author = {};
			
			// hack while we work with old BioStor BibJSON
			if (doc.author[i].forename) {
				author.given = doc.author[i].forename;
			}

			if (doc.author[i].firstname) {
				author.given = doc.author[i].firstname;
			}

			if (doc.author[i].lastname) {
				author.family = doc.author[i].lastname;
			}
			
			if (!author.given && doc.author[i].name) {
				author.literal = doc.author[i].name;
			}
			
			csl.author.push(author);
			
		}
	}
		
	
	// Book
	if (doc.publisher) {
		csl.publisher = doc.publisher.name;
		
		if (doc.publisher.address) {
			csl['publisher-place'] = doc.publisher.address;
		}
		
		if (doc.pages) {
			csl.page = doc.pages.replace(/--/, '-');	
		}
	}
 	
 	
	// Chapter
	if (doc.book) {
		if (doc.book.title) {
			csl['container-title'] = doc.book.title;
		}
		if (doc.pages) {
			csl.page = doc.pages.replace(/--/, '-');	
		}
		
		if (doc.book.publisher) {
			csl.publisher = doc.book.publisher.name;
		
			if (doc.book.publisher.address) {
				csl['publisher-place'] = doc.book.publisher.address;
			}		
		}
		
		if (doc.book.editor) {
			csl.editor = [];
		
			for (var i in doc.author) {
				var author = {};
			
				// hack while we work with old BioStor BibJSON
				if (doc.book.editor[i].forename) {
					author.given = doc.book.editor[i].forename;
				}

				if (doc.book.editor[i].firstname) {
					author.given = doc.book.editor[i].firstname;
				}

				if (doc.book.editor[i].lastname) {
					author.family = doc.book.editor[i].lastname;
				}
			
				if (!author.given && doc.book.editor[i].name) {
					author.literal = doc.book.editor[i].name;
				}
			
				csl.editor.push(author);
			
			}
		}		
	}
	
	// container
    if (doc.journal) {
		if (doc.journal.name) {
			csl['container-title'] = doc.journal.name;
		}

		if (doc.journal.series) {
			csl['collection-title'] = doc.journal.series;
		}
	
		// volume
		if (doc.journal.volume) {
			csl.volume = doc.journal.volume;		
		} 

		// issue
		if (doc.journal.issue) {
			csl.issue = doc.journal.issue;		
		} 
	
		// page
		if (doc.journal.pages) {
			csl.page = doc.journal.pages.replace(/--/, '-');		
		} 
	
		// issn, oclc
		if (doc.journal.identifier)	{
			for (var i in doc.journal.identifier) {
				switch (doc.journal.identifier[i].type) {
					case 'issn':
						if (!csl.ISSN) {
							csl.ISSN = [];
						}
						csl.ISSN.push(doc.journal.identifier[i].id);
						break;
			
					default:
						break;
				}
			}
		}
	}	

	// identifiers
	if (doc.identifier) {
		for (var i in doc.identifier) {
			switch (doc.identifier[i].type) {
			
				case 'biostor':
					csl.URL = 'https://biostor.org/reference/' + doc.identifier[i].id;
					break;

				case 'doi':
					csl.DOI = doc.identifier[i].id;
					break;
					
				case 'isbn':
				case 'isbn13':
					csl.ISBN = doc.identifier[i].id;
					break;
							
				default:
					break;
			}
		}
	} 
	
	return csl;
}
//----------------------------------------------------------------------------------------
function add_values(edoc, key, value, boost) {
	edoc.search_data.fulltext_values.push(value);
	
	boosted = (typeof boost !== 'undefined') ?  boost : false;
	
	if (boosted) {
		edoc.search_data.fulltext_boosted_values.push(value);
	}
	
	switch (key) {
		case 'classification':
		case 'container':
		case 'author':
			edoc.search_data[key].push(value);
			break;
			
		case 'geometry':	
		case 'type':
		case 'year':
			edoc.search_data[key] = value;
			break;
	
		default:
			break;
	}
	
	return edoc;
}

//----------------------------------------------------------------------------------------
function message(doc) {
  if (doc._id.match(/biostor/)) {


    var edoc = {};
    
    edoc.id = doc._id;
    edoc.id =  edoc.id.replace(/\//, '-');
    
    // type of document
    edoc.type = doc.type;
    
	// output to display in list of hits
	edoc.search_result_data = {};
	
	// possible fields to hold information on how to display this object
	edoc.search_result_data.name = '';
	edoc.search_result_data.description = '';
	edoc.search_result_data.thumbnailUrl = '';
	edoc.search_result_data.url = '';

	// BioStor
	//edoc.search_result_data.url = 'https://biostor.org/reference/' + doc._id.replace(/biostor\//, '');
	//edoc.search_result_data.thumbnailUrl = 'https://biostor.org/reference/' + doc._id.replace(/biostor\//, '') + '/thumbnail';
	
	
	// Get thumbnail and URL, and make sure bhl_pages is always an array when 
	// we send it to Elasticsearch otherwise indexing fails as we mix arrays and objects
	//
	if (doc.bhl_pages) {
	
	  edoc.search_result_data.bhl_pages = [];	  

	  if (Array.isArray(doc.bhl_pages)) {
	    edoc.search_result_data.thumbnailUrl = 'https://www.biodiversitylibrary.org/pagethumb/' + doc.bhl_pages[0] + ',60,60';
	    edoc.search_result_data.url = 'https://www.biodiversitylibrary.org/page/' + doc.bhl_pages[0];
	    
	    edoc.search_result_data.bhl_pages = doc.bhl_pages;
	  } else {
	   var key = Object.keys(doc.bhl_pages)[0];
	   edoc.search_result_data.thumbnailUrl = 'https://www.biodiversitylibrary.org/pagethumb/' + doc.bhl_pages[key] + ',60,60';
	   edoc.search_result_data.url = 'https://www.biodiversitylibrary.org/page/' + doc.bhl_pages[key];
	   
	   for (var i in doc.bhl_pages) {
	   	edoc.search_result_data.bhl_pages.push(doc.bhl_pages[i]);
	   }
	   
	  }
	}
	
	// temporary
	edoc.search_result_data.description_parts = [];
	
	/*
	if ($url)
	{
		$doc->search_result_data->url = $url;
	}
	*/

	// fields that will be searched on
	edoc.search_data = {};
	
	// text fields for searching on
	edoc.search_data.fulltext_values = [];
	edoc.search_data.fulltext_boosted_values = [];
	
	// things to use as facets
	edoc.search_data.container = [];
	edoc.search_data.author = [];
	edoc.search_data.year = null;
	edoc.search_data.classification = [];
	
	
	// regular
	
	// type
	edoc.search_data.type = doc.type, false;
	
	// title

	if (doc.title) {
		edoc = add_values(edoc, 'title', doc.title, true);
		edoc.search_result_data.name = doc.title;
	}
	
	// container
    if (doc.journal) {
		if (doc.journal.name) {
			var container = doc.journal.name;
			edoc = add_values(edoc, 'container', container, true);
			edoc.search_result_data.description_parts.push('in ' + container);	
		}
	}		
	
	// author
	if (doc.author) {
		for (var i in doc.author) {
    		// simple case
    		var name = [];
    		
    		if (doc.author[i].firstname) {
    			name.push(doc.author[i].firstname);
    		}
     		if (doc.author[i].lastname) {
    			name.push(doc.author[i].lastname);
    		}
 			// just use literal if we have it
     		if (doc.author[i].name) {
    			name = [doc.author[i].name];
    		}
    		
    		if (name.length > 0) {
    			add_values(edoc, 'author', name.join(' '), true); 
     		}
		}
	}
		
	//------------------------------------------------------------------------------------

	// date
	if (doc.year) {
		edoc = add_values(edoc, 'year', parseInt(doc.year));			
		edoc.search_result_data.description_parts.push('in ' + doc.year);		
	} 

	// volume
	if (doc.journal.volume) {
		edoc = add_values(edoc, 'volume', doc.journal.volume);		
		edoc.search_result_data.description_parts.push('in volume ' + doc.journal.volume);		
	} 

	// issue
	if (doc.journal.issue) {
		edoc = add_values(edoc, 'issue', doc.journal.issue);		
		edoc.search_result_data.description_parts.push('issue ' + doc.journal.issue);		
	} 

	// page
	if (doc.journal.pages) {
		edoc = add_values(edoc, 'page', doc.journal.pages);
		var prefix = 'page';
		if (doc.journal.pages.match(/-/)) {
			prefix = 'pages';
		}
		edoc.search_result_data.description_parts.push(prefix + ' ' + doc.journal.pages.replace(/--/, '-'));		
	} 

	// DOI
	if (doc.DOI) {
		edoc = add_values(edoc, 'doi', doc.message.DOI);		
		edoc.search_result_data.doi = doc.message.DOI;
	} 
	

	//------------------------------------------------------------------------------------
	// classification
    if (doc.classification) {
    	for (var i in doc.classification) {
			edoc = add_values(edoc, 'classification', doc.classification[i], false);
		}
	}
	
	//------------------------------------------------------------------------------------
	
	
	// geo
    if (doc.geometry) {
    	edoc.search_data.geometry = doc.geometry, false;
	}
	
	if (doc.text) {
	  edoc = add_values(edoc, 'text', doc.text[0], false);
	}
	
	//------------------------------------------------------------------------------------
    // citation-js
    edoc.search_result_data.csl = to_csl(doc);
	
	//------------------------------------------------------------------------------------
	// cleanup
	edoc.search_data.fulltext = edoc.search_data.fulltext_values.join(' ');
	delete edoc.search_data.fulltext_values;

	edoc.search_data.fulltext_boosted = edoc.search_data.fulltext_boosted_values.join(' ');
	delete edoc.search_data.fulltext_boosted_values;
	
	edoc.search_result_data.description = 'Published ' + edoc.search_result_data.description_parts.join(', ');
	delete edoc.search_result_data.description_parts;
	
	if (!edoc.search_result_data.thumbnailUrl) {
		delete edoc.search_result_data.thumbnailUrl;
	}

	if (!edoc.search_result_data.url) {
		delete edoc.search_result_data.url;
	}
	
	//$('#jsonld').html(JSON.stringify(edoc, null, 2));

    emit(doc._id, edoc);
    

  }
}

function(doc) {
  if (doc._id.match(/biostor/)) {
    message(doc);
  }
}