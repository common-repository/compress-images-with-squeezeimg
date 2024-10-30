<?php
require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/includes/class-imagecompresssqueezeimg-db.php';
require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-helper.php';
require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-helper-image.php';

use ImagecompresssqueezeimgDataBase\ImagecompresssqueezeimgDb as connect;
use ImageHelperAllInOne\adminHelperImages as HelperImages;
$connction = new connect();
$helperImages = new HelperImages($connction);
$cronOptions = $helperImages->getCronOptions();

$settings = $connction->getSettings();
$treeFoldr = $helperImages->getTree();
$loaders = getPrloadersPath();


$preloaderPath = IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/images/prealoaders';
$preloaderUrl = plugins_url('admin/images/check.png', IMAGECOMPRESSSQUEEZ_PLUGIN_FILE);
$srv = imagecompresssqueezeimg_checkServer($preloaderUrl);

?>

<?php if ($srv == 'nginx' and (!isset($settings['ngins_notise_display']) or $settings['ngins_notise_display'] == 'D')) { ?>
    <div class="nginx-attantion-notice">
        <span>For the plugin to work correctly, you need to add the <a target="_blank" href="<?php echo plugins_url('/nginx.txt', IMAGECOMPRESSSQUEEZ_PLUGIN_FILE); ?>">following</a> settings to the Nginx server configuration</span>
        <button class="button button-primary no-outline" id="close-nginx-attantion-notice"><?php esc_html_e('Ok','imagecompresssqueezeimg');?></button>
    </div>
<?php } ?>

<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_count_send_images_in_request"><?php echo esc_html($helperImages->getSetting('count_send_images_in_request')) ?></span>
<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_api_token"><?php echo esc_html($helperImages->getSetting('api_token')) ?></span>
<div class="imagecompresssqueezeimg-main-content">
    <div class="imagecompresssqueezeimg-blocker display-none-imagecompresssqueezeimg"></div>
    <div id="imagecompresssqueezeimg-nav-tab-header">
        <ul>
            <li><a class="active" data-nav-target="options"><?php esc_html_e('Options','imagecompresssqueezeimg');?></a></li>
            <li><a data-nav-target="compress"><?php esc_html_e('Compress and convert','imagecompresssqueezeimg');?></a></li>
            <li><a data-nav-target="folder"><?php esc_html_e('Convert folder','imagecompresssqueezeimg');?></a></li>
            <li><a data-nav-target="cron"><?php esc_html_e('Cron','imagecompresssqueezeimg');?></a></li>
            <li><a data-nav-target="media"><?php esc_html_e('Media','imagecompresssqueezeimg');?></a></li>
        </ul>
    </div>
    <br><hr>



    <form method="POST" id="imagecompresssqueezeimg-settings-form" action="">
        <input type="hidden" name="ngins_notise_display" value="D">

        <div data-nav-action="options" class="imagecompresssqueezeimg-nav-block">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Enable module','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'status');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Convert images to webp format','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'convert_images_to_webp_format');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Convert images to jp2 format','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'convert_images_to_jp2_format');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Convert images to avif format','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'convert_images_to_avif_format');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Quality of compress','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicSelect($settings,'quality_of_compress', [
                            ['val' => 60, 'text' => 60],
                            ['val' => 70, 'text' => 70],
                            ['val' => 80, 'text' => 80],
                            ['val' => 90, 'text' => 90],
                        ]);?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('gzip enabled','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'gzip_enabled');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Count send images in request','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicSelect($settings,'count_send_images_in_request', [
                            ['val' => 1, 'text' => 1],
                            ['val' => 10, 'text' => 10],
                            ['val' => 50, 'text' => 50],
                            ['val' => 100, 'text' => 100],
                            ['val' => 500, 'text' => 500],
                        ]);?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Lazy load','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'lazy_load');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <span style="margin-top: 13px; "><?php _e('Replace origin images','imagecompresssqueezeimg');?></span>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'replace_origin_images');?>
                    </td>
                    <td>
                    <span>
                        <?php _e('WARNING !!!','imagecompresssqueezeimg');?>
                        <br><?php _e('Replaces original images with compressed ones.','imagecompresssqueezeimg');?>
                        <br><b><?php _e('WITHOUT RESTORATION POSSIBILITY!','imagecompresssqueezeimg');?></b>
                    </span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e('Sitemap','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td colspan="2">
                        <?php echo getMagicInput($settings,'sitemap');?>

                        <button
                                type="submit"
                                class="button button-primary no-outline"
                                id="create-sitemap-xml"
                        >
                            <?php _e('Create xml','imagecompresssqueezeimg');?>
                        </button>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php _e("Token from <a target='_blank' href='https://squeezeimg.com/' >Squeezeimg.com</a> ",'imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td colspan="2">
                        <?php echo getMagicInput($settings,'api_token');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses" style="padding-top: 40px;">
                            <?php _e("Images for loader",'imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td colspan="2">
                        <div class="flex-inline-select">
                            <?php if(!empty($loaders['files'])){ ?>
                                <?php foreach ($loaders['files'] as $key => $value) { ?>
                                    <div class="preloader-rable">
                                        <div class="preloader-radio-input" >
                                            <input type="radio" name="squeezeimg_loader"
                                                <?php if(isset($settings['squeezeimg_loader']) and urldecode($settings['squeezeimg_loader']) == $value){ ?>
                                                    checked="checked"
                                                <?php } ?>
                                                   value="<?php echo esc_html($value);?>" id="input-image<?php echo esc_html($key);?>">
                                        </div>
                                        <div class="preloader-radio-file">
                                            <label for="input-imagee<?php echo esc_html($key);?>">
                                                <img src="<?php echo esc_html($value);?>" alt="" title="" >
                                            </label>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <p style="padding-left: 10px;"><?php esc_html_e("To add images, You need to upload an image to a folder:", 'imagecompresssqueezeimg'); echo esc_html($loaders['folder']);?> </p>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div data-nav-action="compress" class="imagecompresssqueezeimg-nav-block display-none-imagecompresssqueezeimg">

            <div class="col-sm-12 hidden" id="img_compress_block">
                <section class="download-files">
                    <div class="container-fluid">
                        <div class="col-lg-12 ">
                            <div class="download-files-container">
                                <div class="download-files-image__wrapp">
                                    <div class="download-files__image">
                                        <img src="" alt="result" id="img_origin" style="max-height: 360px;width: auto;">
                                        <p> <?php _e("Origin image",'imagecompresssqueezeimg');?> </p>
                                    </div>
                                    <div class="download-files__image">
                                        <img src="" alt="result" id="img_compress" style="max-height: 360px;width: auto;">
                                        <p><?php _e("Optimized image",'imagecompresssqueezeimg');?></p>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </section>
            </div>
            <?php foreach ($helperImages->getAllImageTypes() as $key => $convert){?>
                <div class="col-sm-12 d-flex-center">
                    <div
                        <?php if($helperImages->countAllImage == 0){ ?> style="display:none"<?php }?>
                            id="<?php echo esc_html($key); ?>_path" class="col-sm-6 text-left block-object">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <?php esc_html_e("Convert images in ",'imagecompresssqueezeimg');?><?php echo esc_html($key); ?>
                            </li>
                            <li class="list-group-item">
                                <span class="badge all-count"><?php echo esc_html($helperImages->countAllImage); ?></span>
                                <?php esc_html_e("images origin",'imagecompresssqueezeimg');?>
                            </li>
                            <li class="list-group-item">
                                <span class="badge result"><?php echo esc_html($convert); ?></span>
                                <div style="width: <?php if(($convert *100/$helperImages->countAllImage) > 100){ ?>100<?php }else{ echo esc_html($convert *100/$helperImages->countAllImage);  }?>%"
                                     class="load-progress"></div>
                                <span class="span-text"><?php esc_html_e("Converted ",'imagecompresssqueezeimg');?><?php echo esc_html($key); ?></span>
                            </li>
                            <li class="list-group-item error hidden">
                                <span class="badge  result-error">0</span>
                                <span class="span-text"><?php esc_html_e("Error ",'imagecompresssqueezeimg');?><?php echo esc_html($key); ?></span>
                            </li>
                            <li class="list-group-item text-center">
                                <a class="btn btn-success compress-convert-btn show-btn" data-id="<?php echo esc_html($key); ?>"
                                   href="javascript:void(0);">
                                <span class="compress-btn-not-pressed-text">
                                    <?php esc_html_e("Compress & convert ",'imagecompresssqueezeimg');?>
                                </span>
                                    <span class="compress-btn-pressed-text">
                                    <?php esc_html_e("Stop ",'imagecompresssqueezeimg');?>
                                </span>
                                </a>

                                <a
                                        class="btn btn-danger return-original-btn "
                                        data-id="<?php echo esc_html($key); ?>"
                                        href="javascript:void(0);"
                                    <?php if($helperImages->getSetting('replace_origin_images') == 'A' and $key == 'jpg' ){ ?>
                                        style="display: none;"
                                    <?php } ?>
                                >
                                    <?php esc_html_e("Return original",'imagecompresssqueezeimg');?>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div <?php if($helperImages->countAllImage == 0){ ?> style="display:none"<?php } ?>
                            id="<?php echo esc_html($key); ?>_try" class="col-sm-6 text-left block-object-try">
                        <div class="form-group ">
                            <div class="parent-row">
                                <a href="javascript:void(0);" class="btn btn-primary try_compress" data-id="<?php echo esc_html($key); ?>"><?php esc_html_e("Try compress",'imagecompresssqueezeimg');?></a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div data-nav-action="folder" class="imagecompresssqueezeimg-nav-block display-none-imagecompresssqueezeimg">
            <div class="panel-body form-horizontal">
                <div class="form-group display-grid-form-top">
                    <div class="col-sm-2"></div>
                    <div class="display-grid-form-top-input">
                        <input type="text" name="folder" id="input-status_folder" class="form-control" placeholder="<?php esc_html_e("Select folder",'imagecompresssqueezeimg');?>" data-toggle="modal" data-target="#selectFolder">
                        <input type="hidden" name="format"  id="format" value="jpg">
                        <input type="hidden" name="level" id="level" value="80">
                        <input id="imagecompresssqueezeimg_root_dir" type="hidden" name="root_dir" value="">
                    </div>
                    <div class="display-grid-form-top-button">
                        <input
                                type="submit"
                                disabled
                                class="btn btn-primary"
                                id="btn-text"
                                value="<?php esc_html_e(" Compress",'imagecompresssqueezeimg');?>"
                                data-jpg="<?php esc_html_e(" Compress",'imagecompresssqueezeimg');?>"
                                data-webp="<?php esc_html_e(" Compress and Convert",'imagecompresssqueezeimg');?>"
                                data-jp2="<?php esc_html_e(" Compress and Convert",'imagecompresssqueezeimg');?>"
                                data-avif="<?php esc_html_e(" Compress and Convert",'imagecompresssqueezeimg');?>"
                                data-toggle="modal"
                                data-target="#compressImages"
                        >
                    </div>
                </div>
                <div class="form-group display-grid-form">
                    <div class="col-sm-4 control-label">
                        <label> <?php esc_html_e(" Convert Options",'imagecompresssqueezeimg');?></label>
                    </div>
                    <div class="col-sm-8">
                        <ul class="list-group list-group-horizontal ">
                            <li class="list-group-items active-list" data-value="jpg" data-key="#format"><?php esc_html_e(" Compress",'imagecompresssqueezeimg');?></li>
                            <li class="list-group-items" data-value="webp" data-key="#format">webp</li>
                            <li class="list-group-items" data-value="jp2" data-key="#format">jp2</li>
                            <li class="list-group-items" data-value="avif" data-key="#format">avif</li>
                        </ul>
                    </div>
                </div>
                <div class="form-group display-grid-form">
                    <div class="col-sm-4 control-label">
                        <label><?php esc_html_e(" Compression level",'imagecompresssqueezeimg');?> </label>
                    </div>
                    <div class="col-sm-8">
                        <ul class="list-group list-group-horizontal">
                            <li class="list-group-items active-list" data-value="80" data-key="#level"><?php esc_html_e(" Low",'imagecompresssqueezeimg');?></li>
                            <li class="list-group-items" data-value="70" data-key="#level"><?php esc_html_e(" Easy",'imagecompresssqueezeimg');?></li>
                            <li class="list-group-items" data-value="60" data-key="#level"><?php esc_html_e(" Hight",'imagecompresssqueezeimg');?></li>
                        </ul>
                    </div>
                </div>

                <div class="form-group display-grid-form">
                    <div class="col-sm-4 control-label">
                        <label><?php esc_html_e(" Reduces disk space",'imagecompresssqueezeimg');?></label>
                    </div>
                    <div class="col-sm-8">
                        <ul class="list-group list-group-horizontal">
                            <li class="list-group-items active-list">
                                <span id="replase-origin-folder-tab"><?php echo getMagicTrueFalse($settings,'replace_origin_images');?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div data-nav-action="cron" class="imagecompresssqueezeimg-nav-block display-none-imagecompresssqueezeimg">
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php esc_html_e('Enable Cron','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicCheckBox($settings,'cron_status');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php esc_html_e('Cron type','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicSelect($settings,'compress_squeezeimg_cron_type', [
                            ['val' => 'all', 'text' => 'All'],
                            ['val' => 'webp', 'text' => 'Only to webp'],
                            ['val' => 'jp2', 'text' => 'Only to jp2'],
                            ['val' => 'jpg', 'text' => 'Only compress'],
                            ['val' => 'avif', 'text' => 'Only to avif'],
                        ]);?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php esc_html_e('Count send images for cron','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicSelect($settings,'squeezeimg_count_send_cron', [
                            ['val' => 10, 'text' => 10],
                            ['val' => 100, 'text' => 100],
                            ['val' => 200, 'text' => 200],
                            ['val' => 500, 'text' => 500],
                            ['val' => 1000, 'text' => 1000],
                            ['val' => 5000, 'text' => 5000],
                        ]);?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses">
                            <?php esc_html_e('Call time','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td>
                        <?php echo getMagicSelect($settings,'call_cron_time', $cronOptions);?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>


        <div data-nav-action="media" class="imagecompresssqueezeimg-nav-block display-none-imagecompresssqueezeimg">
            <table class="form-table">
                <tbody>

                <?php foreach (IMAGECOMPRESS_FORMATS as $format){ ?>
                    <tr>
                        <th scope="row">
                            <label for="statuses">
                                <?php printf(
                                /* translators: %s: file format */
                                    esc_html__('Convert images to %s when uploading to media', 'imagecompresssqueezeimg'),
                                    $format
                                );
                                ?>
                            </label>
                        </th>
                        <td class="<?php echo esc_html($format);?>-thumbnail-target">
                            <?php echo getMagicCheckBox($settings,$format . '_convert_upload');?>
                        </td>
                    </tr>

                    <tr class="minor-block <?php echo esc_html($format);?>-thumbnail-block <?php if((isset($settings[$format . '_convert_upload']) and $settings[$format . '_convert_upload'] != 'A') or !isset($settings[$format . '_convert_upload'])){ echo 'thumbnail_notdisplay';} ?>">
                        <th scope="row">
                            <label for="statuses">
                                <span style="margin-top: 13px; position: absolute;"><?php printf(
                                    /* translators: %s: image format */
                                        esc_html__('Compress %s thumbnail', 'imagecompresssqueezeimg'),
                                        esc_html($format)
                                    );
                                    ?></span>
                            </label>
                        </th>
                        <td>
                            <?php echo getMagicCheckBox($settings,'compress_thumbnail_' . $format);?>
                        </td>
                        <td>
                        <span>
                            <?php esc_html_e('WARNING !!!','imagecompresssqueezeimg');?>
                            <br><?php esc_html_e('Each media file can have multiple thumbnails','imagecompresssqueezeimg');?>
                            <br><b><?php esc_html_e('Each thumbnail will be compressed separately!','imagecompresssqueezeimg');?></b>
                        </span>
                        </td>
                    </tr>
                <?php } ?>

                </tbody>
            </table>
        </div>



        <table>
            <tbody>
            <tr>
                <th scope="row">

                    <button
                            type="submit"
                            class="button button-primary no-outline"
                            id="save-settings-button"
                    >
                        <?php esc_html_e('Save','imagecompresssqueezeimg');?>
                    </button>

                </th>
                <td>
                            <span class="alert-imagecompresssqueezeimg succes-imagecompresssqueezeimg display-none-imagecompresssqueezeimg">
                                <?php esc_html_e('Settings saved successfully ','imagecompresssqueezeimg');?>
                            </span>
                    <span class="alert-imagecompresssqueezeimg error-imagecompresssqueezeimg display-none-imagecompresssqueezeimg">
                                <?php esc_html_e('Settings not saved ','imagecompresssqueezeimg');?>
                            </span>
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>
<div class="imagecompresssqueezeimg-modal-background imagecompresssqueezeimg-fade imagecompresssqueezeimg-modal-folder imagecompresssqueezeimg-modal-image"></div>
<div class="imagecompresssqueezeimg-modal imagecompresssqueezeimg-fade imagecompresssqueezeimg-modal-folder" id="selectFolder" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle"><?php esc_html_e(" Selected folder",'imagecompresssqueezeimg');?></h5>
                <button type="button" class="imagecompresssqueezeimg-modal-folder-close imagecompresssqueezeimg-modal-folder-close-button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <hr>
            <div class="modal-body">
                <div id="selectFolder">

                    <ul class="list">
                        <?php if($treeFoldr){?>
                            <?php foreach ($treeFoldr as $key => $folder){ ?>
                                <li  data-path="<?php echo esc_html($folder);?>" data-key="<?php echo esc_html($key);?>">
                                    <?php $name = $folder ?>
                                    <span class="icon-folder"></span>
                                    <span class="name"><?php echo esc_html($name); ?></span>
                                    <ul class="sub-list"></ul>
                                </li>
                            <?php }?>
                        <?php } ?>
                    </ul>

                </div>

            </div>
            <hr>
            <div class="modal-footer">
                <input type="hidden" id="check_folder">
                <button type="button" class="btn btn-secondary imagecompresssqueezeimg-modal-folder-close-button" data-dismiss="modal" ><?php esc_html_e(" Close",'imagecompresssqueezeimg');?></button>
                <button type="button" class="btn btn-primary" id="btn-to-save" data-dismiss="modal"><?php esc_html_e(" Selected folder",'imagecompresssqueezeimg');?></button>
            </div>

        </div>
    </div>
</div>

<div class="imagecompresssqueezeimg-modal imagecompresssqueezeimg-modal-image imagecompresssqueezeimg-fade" id="compressImages" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle"><?php esc_html_e(" Procesing...",'imagecompresssqueezeimg');?></h5>
                <button type="button" class="imagecompresssqueezeimg-modal-folder-close imagecompresssqueezeimg-modal-image-close-button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <hr>
                <table id="process-table" class="table">
                    <tr>
                        <td><?php esc_html_e(" Images ",'imagecompresssqueezeimg');?></td>
                        <td> <?php esc_html_e(" Origin size",'imagecompresssqueezeimg');?></td>
                        <td><?php esc_html_e(" Compress size",'imagecompresssqueezeimg');?></td>
                        <td><?php esc_html_e(" Status",'imagecompresssqueezeimg');?></td>
                    </tr>
                </table>
                <hr>
            </div>
            <div class="modal-footer">
                <button type="button" id="imagesProcces" class="btn btn-secondary imagecompresssqueezeimg-modal-image-close-button" data-dismiss="modal" ><?php esc_html_e(" Close",'imagecompresssqueezeimg');?></button>
            </div>

        </div>
    </div>
</div>