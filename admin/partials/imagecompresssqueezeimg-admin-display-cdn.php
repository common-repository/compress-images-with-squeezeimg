<?php

$cdn = 'D';

if (isset($this->settings['squeeze_cdn_service']) && $this->settings['squeeze_cdn_service'] == 'A') {
    $cdn = 'A';
}

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
$needCompressed = count($connction->getCdnNotCompressed(false));
$countAllImage = count($connction->countAllImage());
$currentCompressed = $countAllImage - $needCompressed;
if ($countNewImages > 0) {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php printf(
            /* translators: %d: number of new images */
                esc_html__('New images added: %d', 'my-text-domain'),
                intval($countNewImages)
            );
            ?></p>
    </div>
    <?php
}
?>


<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_count_send_images_in_request"><?php echo esc_html($helperImages->getSetting('count_send_images_in_request')) ?></span>
<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_api_token"><?php echo esc_html($helperImages->getSetting('api_token')) ?></span>
<div class="imagecompresssqueezeimg-main-content">
    <div class="imagecompresssqueezeimg-blocker display-none-imagecompresssqueezeimg"></div>
    <div id="imagecompresssqueezeimg-nav-tab-header">
        <ul>
            <li><a class="active" data-nav-target="options"><?php esc_html_e('Options','imagecompresssqueezeimg');?></a></li>
            <li><a data-nav-target="Lazyload"><?php esc_html_e('Lazy load','imagecompresssqueezeimg');?></a></li>
        </ul>
    </div>
    <br>

    <hr>

    <form method="POST" id="imagecompresssqueezeimg-settings-form" action="">

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
                        <?php echo getMagicCheckBox($settings,'status_cdn');?>
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
                            <?php _e('Sitemap','imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td colspan="2">
                        <?php echo getMagicInput($settings,'sitemap');?>

                        <button
                            type="submit"
                            class="button button-primary no-outline"
                            data-id="webp"
                            id="create-sitemap-xml"
                        >
                            <?php _e('Create xml','imagecompresssqueezeimg');?>
                        </button>
                    </td>

                </tr>


                </tbody>
            </table>
            <div class="col-sm-12 d-flex-center">
                <div
                        id="sdn_path" class="col-sm-6 text-left block-object">
                    <ul class="list-group">
                        <li class="list-group-item">
                            <?php esc_html_e("Compress in cdn ",'imagecompresssqueezeimg');?>
                        </li>
                        <li class="list-group-item">
                            <span class="badge all-count"><?php echo esc_html($countAllImage); ?></span>
                            <?php esc_html_e("images origin",'imagecompresssqueezeimg');?>
                        </li>
                        <li class="list-group-item">
                            <span style="background-color: green;" class="badge result"><?php echo esc_html($countAllImage - $needCompressed); ?></span>
                            <div style="width: <?php if($currentCompressed *100/$countAllImage > 100){ ?>100<?php }else{ echo esc_html($currentCompressed *100/$countAllImage);  }?>%"
                                 class="load-progress"></div>
                            <span class="span-text"><?php esc_html_e("Compressed ",'imagecompresssqueezeimg');?></span>
                        </li>
                        <li class="list-group-item">
                            <span style="background-color: darkred;" class="badge  need-cdn"><?php echo esc_html($needCompressed); ?></span>
                            <span class="span-text"><?php esc_html_e("Need compress ",'imagecompresssqueezeimg');?></span>
                        </li>
                        <li class="list-group-item text-center">
                            <button
                                    type="submit"
                                    class="button button-primary no-outline"
                                    id="start-cdn-compress-image"
                            >
                                <?php _e('Load images','imagecompresssqueezeimg');?>
                            </button>
                            <button
                                    type="submit"
                                    class="button button-primary no-outline"
                                    id="clear-cdn-compress-image"
                            >
                                <?php _e('Purge cdn','imagecompresssqueezeimg');?>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div data-nav-action="Lazyload" class="imagecompresssqueezeimg-nav-block display-none-imagecompresssqueezeimg">
        <table class="form-table">
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
        </table>
    </div>

    </form>

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
</div>