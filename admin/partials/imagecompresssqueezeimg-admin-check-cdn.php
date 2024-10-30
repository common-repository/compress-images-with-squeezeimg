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
$preloaderUrl = plugins_url('admin/images/loader.gif', IMAGECOMPRESSSQUEEZ_PLUGIN_FILE);

?>



<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_count_send_images_in_request"><?php echo esc_html($helperImages->getSetting('count_send_images_in_request')) ?></span>
<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_api_token"><?php echo esc_html($helperImages->getSetting('api_token')) ?></span>
<div class="imagecompresssqueezeimg-check-cdn-content">


    <div class="imagecompresssqueezeimg-check-cdn-form" >

        <form method="POST" id="imagecompresssqueezeimg-settings-form" action="">
            <div class="imagecompresssqueezeimg-blocker-cdn">
                <div style="height: 100%;position: relative;overflow: hidden">
                    <img style="margin: auto;position: absolute;top: 50%;transform: translate(-50%, -50%);left: 50%;height: 50px;" src="<?php echo $preloaderUrl; ?>")>
                </div>
            </div>

            <table >
                <tbody>
                <tr>
                    <th colspan="3" scope="row">
                        <label for="statuses" style="display: grid;justify-items: start;">
                        <?php echo _e('To use CDN, please fill out the form'); ?>
                        </label>
                        <hr>
                    </th>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses" style="display: grid;justify-items: start;">
                            <?php _e("Token from <a target='_blank' href='https://squeezeimg.com/' > Squeezeimg.com</a> ",'imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td colspan="2">
                        <?php echo getMagicInput($settings,'api_token');?>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="statuses" style="align-content: start;display: flex;">
                            <?php _e("Domain name ",'imagecompresssqueezeimg');?>
                        </label>
                    </th>
                    <td colspan="2">
                        <?php echo getMagicInput($settings,'domain_name', $_SERVER['SERVER_NAME']);?>
                    </td>
                </tr>

                <tr>
                    <td colspan="3">
                        <hr>
                    </td>
                </tr>
                <tr>
                    <th scope="row" style="align-content: start;display: flex;">

                        <button
                                type="submit"
                                class="button button-primary no-outline"
                                id="save-cdn-config"
                        >
                            <?php esc_html_e('Continue','imagecompresssqueezeimg');?>
                        </button>
                    </th>
                    <td colspan="2">
                        <span id="cdn-validate-result"></span>
                    </td>
                </tr>
                </tbody>
                </tbody>
            </table>


        </form>
    </div>
</div>