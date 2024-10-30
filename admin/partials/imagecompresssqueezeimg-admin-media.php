<?php
    require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/includes/class-imagecompresssqueezeimg-db.php';
//    require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-helper.php';
    require_once IMAGECOMPRESSSQUEEZ_PLUGIN_DIR . '/admin/partials/imagecompresssqueezeimg-admin-helper-image.php';
    use ImagecompresssqueezeimgDataBase\ImagecompresssqueezeimgDb as connect;
    use ImageHelperAllInOne\adminHelperImages as HelperImages;

    $connction = new connect();
    $helperImages = new HelperImages($connction, IMAGECOMPRESSSQUEEZ_CONTENT_BASE . '/uploads');
    $cronOptions = $helperImages->getCronOptions();
//    $settings = $connction->getSettings();
//    $treeFoldr = $helperImages->getTree();
//    $loaders = getPrloadersPath();
?>

<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_count_send_images_in_request"><?php echo esc_html($helperImages->getSetting('count_send_images_in_request')) ?></span>
<span class="display-none-imagecompresssqueezeimg" id="squeezeimg_api_token"><?php echo esc_html($helperImages->getSetting('api_token')) ?></span>
<div class="imagecompresssqueezeimg-main-content">
    <div class="imagecompresssqueezeimg-blocker display-none-imagecompresssqueezeimg"></div>

    <form method="POST" id="imagecompresssqueezeimg-settings-form" action="">
        <input id="imagecompresssqueezeimg_root_dir" type="hidden" name="root_dir" value="media">
        <div data-nav-action="compress" class="imagecompresssqueezeimg-nav-block">

            <div class="col-sm-12 hidden" id="img_compress_block">
                <section class="download-files">
                    <div class="container-fluid">
                        <div class="col-lg-12 ">
                            <div class="download-files-container">
                                <div class="download-files-image__wrapp">
                                    <div class="download-files__image">
                                        <img src="" alt="result" id="img_origin" style="max-height: 360px;width: auto;">
                                        <p> <?php esc_html_e("Origin image",'imagecompresssqueezeimg');?> </p>
                                    </div>
                                    <div class="download-files__image">
                                        <img src="" alt="result" id="img_compress" style="max-height: 360px;width: auto;">
                                        <p><?php esc_html_e("Optimized image",'imagecompresssqueezeimg');?></p>
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
                            <span class="span-text"><?php esc_html_e("Converted ",'imagecompresssqueezeimg');?><?php echo esc_html($key); ?><?php esc_html_e(" images",'imagecompresssqueezeimg');?></span>
                        </li>
                        <li class="list-group-item error hidden">
                            <span class="badge  result-error">0</span>
                            <span class="span-text"><?php esc_html_e("Error ",'imagecompresssqueezeimg');?><?php echo esc_html($key); ?><?php esc_html_e(" images",'imagecompresssqueezeimg');?></span>
                        </li>
                        <li class="list-group-item text-center">
                            <a class="btn btn-success compress-convert-btn show-btn" data-id="<?php echo esc_html($key); ?>"
                               data-compress-folder=<?php echo IMAGECOMPRESSSQUEEZ_CONTENT_BASE; ?>"/uploads"
                               href="javascript:void(0);"><?php esc_html_e("Compress & convert ",'imagecompresssqueezeimg');?></a>
                            <?php if($helperImages->getSetting('replace_origin_images') == 0 or $key != 'jpg' ){ ?>
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
                            <?php } ?>
                        </li>
                    </ul>
                </div>
            </div>
            <?php } ?>
        </div>

    </form>
</div>
