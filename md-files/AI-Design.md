
## Design

the plugin takes woocommerce audio projects, or maybe even just playlist objects on a page 

upload/bfp/[productid]/demos.mp3 for each product streamed from here
purchased woo-commerce-uploads/ if owned plays directly

audio through REST API with x-send enabled



examine this code for violating single class principle and mixing concerns

examine for best practices in wordpress 2025

make sure logic is grouped into the correct class 
should the player have a renderer does that mean it works for every render? including block and widget does that work in this design? 


concerns atm:
where is URL generation 
are all file operations happening inside files?


AUDIO ENGINE:
fails: HTML5 fallback
default: MEdia element
select: Wavesurfer
etc...

so if we construct a function that hooks onto product generation or updating, if its a downloadable audio product - we just leave it where it was created, usually in woocommerce_uploads/ (or if that can be retrieved by a native function that would be better) - then we pass it off to a function which zips up all of it into various formats and then any user can download any format straight away - we just get ffmpeg to do it every time the product is generated and then no more processing on the fly to do that. then if user owns album it just retrieves purchased url presumably in woocommerce_uploads/ which means this program can just always use default urls to stream through API and urls that are pre-generated from the titles to download zips etc