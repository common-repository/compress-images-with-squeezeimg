# images convert squeezeimg opencart by Pinta Webware

Module works with images from the database
Need Imagick module for PHP

Extension Features:

* add gzip compress for apache server
* save original image files
* add lazyload for images
* convert images to webp/jp2 formats
* auto convert images 
* can change the quality of compress
* can choose files which you want to compress/convert
* the module does not delete your images' classes in frontend

When using nginx server.
To redirect to webp images, you need to add configurations to * .conf of your nginx server
========================================================================================
*  Find in the config the location that processes the images
--------------------------
    location ~* ^.+\.(jpg|jpeg|png|gif|svg|...)$ {
    ...
    }
---------------------------
*  Add webp, jp2 formats inside the brackets

--------------------------
    location ~* ^.+\.(jpg|jpeg|png|webp|jp2|gif|svg|...)$ {
        ...
    }
--------------------------

 * Add rules for interception from converted images (opencart 2.3->3.x)
---------------------------
     location ~* ^.+\.(jpg|jpeg|png|webp|jp2|gif|svg|...)$ {
         ######################################
               location ~* /?(.+\.(jpeg|jpg|png|webp|jp2)) {

                  location ~* /?(.+)(_compress.jpg)$ {
                     try_files $uri /$1 =404;
                     add_header IMAGE-COMPRESS-PRO  link-image_jpg;
                     break;
                  }
                  location ~* /?(.+)\.(webp)$ {
                     try_files $uri /$1 =404;
                     add_header IMAGE-COMPRESS-PRO-FORMAT  $format;
                     add_header IMAGE-COMPRESS-PRO  link-image_webp;
                     break;
                  }
                  location ~* /?(.+)\.(jp2)$ {
                     try_files $uri /$1 =404;
                     add_header Content-Type image/jp2;
                     add_header IMAGE-COMPRESS-PRO-FORMAT  $format;
                     add_header IMAGE-COMPRESS-PRO  link-image_jp2;
                     break;
                  }
                  set $format .jpg;
                  set $safari 0;
                  set $rules_format_jpg 0;
                  set $rules_format_webp 0;
                  set $rules_format_jp2 0;
                  if ($http_accept !~* "webp"){
                       set $safari 1;
                  }
                  if ( -f /var/www/opencart/data/www/storage_3036/images_compress_squeezeimg/jpg.enabled ) {
                      set $rules_format_jpg 1;
                  }
                  if ( -f /var/www/opencart/data/www/storage_3036/images_compress_squeezeimg/webp.enabled ) {
                      set $rules_format_webp 2;
                  }
                  if ( -f /var/www/opencart/data/www/storage_3036/images_compress_squeezeimg/jp2.enabled ) {
                      set $rules_format_jp2 3;
                  }

                  set $check1 $safari$rules_format_jp2;
                  if ( $check1 = 13) {
                       set $format .jp2;
                  }
                  set $check2 $safari$rules_format_jpg;
                  if ( $check2 = 01) {
                      set $format _compress.jpg;
                  }
                  set $check3 $safari$rules_format_webp;
                  if ( $check3 = 02) {
                      set $format .webp;
                  }

                  if ( -f $request_filename$format) {
                     rewrite /?(.+\.(jpe?g|jpg|png)$) $uri$format last;
                     break;
                  }

                     set $rules_fredirect 0;
                     set $rules_zero 0;
                     set $rules_one 1;
                  if ( -f /var/www/opencart/data/www/storage_3036/images_compress_squeezeimg/getimage.php) {
                    set $rules_fredirect  $rules_fredirect$rules_one;
                  }
                  if ($http_accept !~* "images_compress_squeezeimg"){
                     set $rules_fredirect  $rules_fredirect$rules_zero;
                   }

                  if ( $rules_fredirect = 010 ) {
                      rewrite /?(.+)\.(jpe?g|jpg|png)$ /index.php?route=extension/module/images_compress_squeezeimg/getImage&url=$1.$2 last;
                  }

              }
        #########################
               ...
             }

-----------------------------

 * Add rules for interception from converted images (opencart 1.5->2.2)
---------------------------
     location ~* ^.+\.(jpg|jpeg|png|webp|jp2|gif|svg|...)$ {
         	######################################
      location ~* /?(.+\.(jpeg|jpg|png|webp|jp2)) {
          
                         location ~* /?(.+)(_compress.jpg)$ {
                            try_files $uri /$1 =404;
                            add_header IMAGE-COMPRESS-PRO  link-image_jpg;
                            break;
                         }
                         location ~* /?(.+)\.(webp)$ {
                            try_files $uri /$1 =404;
                            add_header IMAGE-COMPRESS-PRO-FORMAT  $format;
                            add_header IMAGE-COMPRESS-PRO  link-image_webp;
                            break;
                         }
                         location ~* /?(.+)\.(jp2)$ {
                            try_files $uri /$1 =404;
                            add_header Content-Type image/jp2;
                            add_header IMAGE-COMPRESS-PRO-FORMAT  $format;
                            add_header IMAGE-COMPRESS-PRO  link-image_jp2;
                            break;
                         }
                         set $format .jpg;
                         set $safari 0;
                         set $rules_format_jpg 0;
                         set $rules_format_webp 0;
                         set $rules_format_jp2 0;
                         if ($http_accept !~* "webp"){
                              set $safari 1;
                         }
                         if ( -f {{DIR_STORAGE}}images_compress_squeezeimg/jpg.enabled ) {
                             set $rules_format_jpg 1;
                         }
                         if ( -f {{DIR_STORAGE}}images_compress_squeezeimg/webp.enabled ) {
                             set $rules_format_webp 2;
                         }
                         if ( -f {{DIR_STORAGE}}images_compress_squeezeimg/jp2.enabled ) {
                             set $rules_format_jp2 3;
                         }
         
                         set $check1 $safari$rules_format_jp2;
                         if ( $check1 = 13) {
                              set $format .jp2;
                         }
                         set $check2 $safari$rules_format_jpg;
                         if ( $check2 = 01) {
                             set $format _compress.jpg;
                         }
                         set $check3 $safari$rules_format_webp;
                         if ( $check3 = 02) {
                             set $format .webp;
                         }
         
                         if ( -f $request_filename$format) {
                            rewrite /?(.+\.(jpe?g|jpg|png)$) $uri$format last;
                            break;
                         }
                            set $rules_fredirect 0;
                                                    
                         if ( -f {{DIR_STORAGE}}images_compress_squeezeimg/getimage.php) {
                           set $rules_fredirect  $rules_fredirect1;
                         }
                         if ($http_accept !~* "images_compress_squeezeimg"){
                            set $rules_fredirect  $rules_fredirect0;
                          }
                         if ( $rules_fredirect = 010 ) {
                             rewrite /?(.+)\.(jpe?g|jpg|png)$ /index.php?route=module/images_compress_squeezeimg/getImage&url=$1.$2 last;
                         }
         
                     }
               #########################
               ...
             }

-----------------------------

# Resolving the conflict with the OpenCart Lightning plugin

* open file DIR_APLICATION."controller/extension/lightning/zero.php"
* find text "php html htm xml yml" 
* and replace to "jpg jpeg png php html htm xml yml"
* save file on server
