# biostor-lite
Simple interface to BioStor



## DNS

Type | Name | Content
-- | -- | --
CNAME | biostor.org | DNS Target from Heroku
CNAME | www | DNS Target from Heroku


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


           
