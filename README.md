# biostor-lite
Simple interface to BioStor



## DNS

Type | Name | Content
-- | -- | --
CNAME | biostor.org | DNS Target from Heroku
CNAME | www | DNS Target from Heroku

## Elastic search




## IIIF

Based on @rogerhyam’s [IIIF proof of concept](https://github.com/rogerhyam/iiif_poc) I’ve implemented a IIIF viewer for BioStor content using [Universal Viewer](https://universalviewer.io) and the Internet Archive’s IIIF server.

To install Universal Viewer clone the respository, make sure you have node.js, bower, and grunt, then build the distribution:

1. Install the grunt command line interface (if you haven't already); on the command line, run:

         npm install -g grunt-cli

1. Install bower (if you haven't already)

        npm install -g bower

1. On the command line, go in to the `universalviewer` folder

1. Run

        npm install
        bower install
        grunt sync

1. Build the distribution build (i.e., files to add to your web server)

        grunt build

1. Put the files in the ```dist``` folder on your web site (e.g., in a folder called ```uv```)

1. Add a tweaked version of Roger’s ```viewer.php``` script from [iiif_poc](https://github.com/rogerhyam/iiif_poc) and use the InternetArchive IIIF server as the source of the IIIF manifest, e.g. [https://iiif.archivelab.org/iiif/biostor-244961/manifest.json](https://iiif.archivelab.org/iiif/biostor-244961/manifest.json).

Note that IA’s IIIF server seems a bit flaky, so your mileage may vary.

           
## Managing items

### Elasticsearch

Use [scroll search](https://www.elastic.co/guide/en/elasticsearch/reference/current/paginate-search-results.html#scroll-search-results) to get list of all ids. Note that this search requires multiple Elasticsearch queries, only the first includes the specific index, all subsequent queries are to the server.


### Internet Archive

https://archive.org/advancedsearch.php?q=collection%3A%28biostor%29&fl%5B%5D=identifier&sort%5B%5D=&sort%5B%5D=&sort%5B%5D=&rows=300000&page=1&output=json&callback=callback&save=yes#raw will get JSON list of all BioStor items in IA.



