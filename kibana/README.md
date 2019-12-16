# Kibana


[c64097ae9ae54e9fb57648cbffe7f41c.europe-west2.gcp.elastic-cloud.com](https://c64097ae9ae54e9fb57648cbffe7f41c.europe-west2.gcp.elastic-cloud.com:9243/app/kibana#/dashboard)

## Map config

```
Data source Documents
Index pattern bslite*
Geospatial field search_data.geometry
Geospatial field type geo_shape
```

key | value 
-- | --
Fill colour | #D06855
Border color | #262626
Border width | 1
Symbol size | 3


## Requests

### Map

```
{
  "size": 10000,
  "_source": {
    "excludes": [],
    "includes": [
      "search_data.geometry"
    ]
  },
  "stored_fields": [
    "search_data.geometry"
  ],
  "script_fields": {},
  "docvalue_fields": [],
  "query": {
    "bool": {
      "must": [],
      "filter": [
        {
          "match_all": {}
        },
        {
          "geo_shape": {
            "search_data.geometry": {
              "shape": {
                "type": "Polygon",
                "coordinates": [
                  [
                    [
                      -133.461735,
                      80.826735
                    ],
                    [
                      -133.461735,
                      -71.375365
                    ],
                    [
                      134.077325,
                      -71.375365
                    ],
                    [
                      134.077325,
                      80.826735
                    ],
                    [
                      -133.461735,
                      80.826735
                    ]
                  ]
                ]
              },
              "relation": "INTERSECTS"
            }
          }
        }
      ],
      "should": [],
      "must_not": []
    }
  }
}
```

### Timeline

```
{
  "aggs": {
    "2": {
      "histogram": {
        "field": "search_data.year",
        "interval": 10,
        "min_doc_count": 0,
        "extended_bounds": {
          "min": 1750,
          "max": 2020
        }
      }
    }
  },
  "size": 0,
  "_source": {
    "excludes": []
  },
  "stored_fields": [
    "*"
  ],
  "script_fields": {},
  "docvalue_fields": [],
  "query": {
    "bool": {
      "must": [],
      "filter": [
        {
          "match_all": {}
        },
        {
          "match_all": {}
        }
      ],
      "should": [],
      "must_not": []
    }
  }
}
```

### Container

```
{
  "aggs": {
    "2": {
      "terms": {
        "field": "search_data.container.keyword",
        "order": {
          "_count": "desc"
        },
        "size": 5
      }
    }
  },
  "size": 0,
  "_source": {
    "excludes": []
  },
  "stored_fields": [
    "*"
  ],
  "script_fields": {},
  "docvalue_fields": [],
  "query": {
    "bool": {
      "must": [],
      "filter": [
        {
          "match_all": {}
        },
        {
          "match_all": {}
        },
        {
          "geo_shape": {
            "ignore_unmapped": true,
            "search_data.geometry": {
              "shape": {
                "type": "Polygon",
                "coordinates": [
                  [
                    [
                      -30.57024,
                      12.29804
                    ],
                    [
                      -30.57024,
                      3.60214
                    ],
                    [
                      -21.95696,
                      3.60214
                    ],
                    [
                      -21.95696,
                      12.29804
                    ],
                    [
                      -30.57024,
                      12.29804
                    ]
                  ]
                ]
              },
              "relation": "INTERSECTS"
            }
          }
        }
      ],
      "should": [],
      "must_not": []
    }
  }
}
```

### Taxon taggs

```
{
  "aggs": {
    "2": {
      "terms": {
        "field": "search_data.classification.keyword",
        "order": {
          "_count": "desc"
        },
        "size": 20
      }
    }
  },
  "size": 0,
  "_source": {
    "excludes": []
  },
  "stored_fields": [
    "*"
  ],
  "script_fields": {},
  "docvalue_fields": [],
  "query": {
    "bool": {
      "must": [],
      "filter": [
        {
          "match_all": {}
        },
        {
          "match_all": {}
        },
        {
          "geo_shape": {
            "ignore_unmapped": true,
            "search_data.geometry": {
              "shape": {
                "type": "Polygon",
                "coordinates": [
                  [
                    [
                      -30.57024,
                      12.29804
                    ],
                    [
                      -30.57024,
                      3.60214
                    ],
                    [
                      -21.95696,
                      3.60214
                    ],
                    [
                      -21.95696,
                      12.29804
                    ],
                    [
                      -30.57024,
                      12.29804
                    ]
                  ]
                ]
              },
              "relation": "INTERSECTS"
            }
          }
        }
      ],
      "should": [],
      "must_not": []
    }
  }
}
```


