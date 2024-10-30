(function( $ ) {
	'use strict';
	jQuery(document).ready( function( $ ) {

		$('#swith_cdn_button').click(function(e) {

			e.preventDefault();
			let cdn = undefined;
			if ($('#swith_cdn_button').attr('data-status') == 'D') {
				cdn = 'A';
			} else {
				cdn = 'D';
			}
			var data = {
				action: 'imagecompresssqueezeimg_settings_helper',
				values: "status_cdn=D&status=D&squeeze_cdn_service=" + cdn
			};
			jQuery.post( ajaxurl, data, function( result ){
				location.reload();
			});

		});


		// sdn aaaaa
		$(document).on('click','.imagecompresssqueezeimg-media-convert-button',function (e) {
			e.preventDefault();
			let images = $(this).attr('data-images');
			let format = $(this).attr('data-format');
			var mediaId = $(this).attr('data-media-id');
			var compressFormat = $(this).attr('data-media-compress-format');
			$('.media-main-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').addClass('icon-loader');
			$('.media-thumbnail-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').addClass('icon-loader');
			$.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {action: 'imagecompresssqueezeimg_media_compress_igm', images_json: images, formatconvert: format, id: mediaId},
				success: function (resp) {
					let result = JSON.parse(resp);
					$('.media-main-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').removeClass('icon-loader');
					$('.media-main-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').text(result.sizes.main + ' KB');
					$('.media-thumbnail-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').removeClass('icon-loader');
					$('.media-thumbnail-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').text(result.sizes.thumb + ' KB');

				}
			});
		});

		$(document).on('click','.imagecompresssqueezeimg-media-restore-backup',function (e) {
			e.preventDefault();
			let images = $(this).attr('data-images');
			let format = $(this).attr('data-format');
			var mediaId = $(this).attr('data-media-id');
			var compressFormat = $(this).attr('data-media-compress-format');
			$('.media-main-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').addClass('icon-loader');
			$('.media-thumbnail-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').addClass('icon-loader');
			$.ajax({
				url: ajaxurl,
				method: 'POST',
				data: {action: 'imagecompresssqueezeimg_media_restore_backup', images_json: images, formatconvert: format, id: mediaId},
				success: function (resp) {
					let result = JSON.parse(resp);

					$('.media-main-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').removeClass('icon-loader');
					$('.media-main-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').text('not compress');
					$('.media-thumbnail-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').removeClass('icon-loader');
					$('.media-thumbnail-image-size-value[data-media-id="' + mediaId + '"][data-media-compress-format="' + compressFormat + '"]').text('not compress');

				}
			});
		});

		$('.jpg-thumbnail-target .imagecompresssqueezeimg-radio-mgic').click(function (){
			$('.jpg-thumbnail-block').fadeToggle();
		});

		$('.webp-thumbnail-target .imagecompresssqueezeimg-radio-mgic').click(function (){
			$('.webp-thumbnail-block').fadeToggle();
		});

		$('.jp2-thumbnail-target .imagecompresssqueezeimg-radio-mgic').click(function (){
			$('.jp2-thumbnail-block').fadeToggle();
		});

		$('.avif-thumbnail-target .imagecompresssqueezeimg-radio-mgic').click(function (){
			$('.avif-thumbnail-block').fadeToggle();
		});

		$('#save-cdn-config').click(function(e){
			e.preventDefault();
			var data = {
				action: 'imagecompresssqueezeimg_check_cdn_config',
				values: $('#imagecompresssqueezeimg-settings-form').serialize()
			};
			$('.imagecompresssqueezeimg-blocker-cdn').css('height', '100%');
			$('#cdn-validate-result').html('');

			jQuery.post( ajaxurl, data, function( result ){
				let response = JSON.parse(result);
				$('.imagecompresssqueezeimg-blocker-cdn').css('height', '0');
				$('#cdn-validate-result').html(response.token + response.hash);

				if (response.status) {
					location.reload();
				}

			} );
		});

		$('#start-cdn-compress-image').click(function(e) {
			e.preventDefault();

			// Функция для обработки порции изображений
			function processImages() {
				var data = {
					action: 'imagecompresssqueezeimg_cdn_compress',
				};

				// Показываем блокировщик и отключаем кнопку
				$('.imagecompresssqueezeimg-blocker').removeClass('display-none-imagecompresssqueezeimg');
				$('#start-cdn-compress-image').attr('disabled', 'disabled');

				jQuery.post(ajaxurl, data, function(result) {
					var allCount = parseInt($(".all-count").text());
					var totalCount = parseInt($('.result').text());
					var count = parseInt(result) + totalCount;

					// Обновляем прогресс
					var loader = Math.min(100, count * 100 / allCount);
					$('.load-progress').css('width', loader + '%');

					// Обновляем счетчики результатов
					if (allCount > count) {
						$('.result').html(count);
						$('.need-cdn').html(allCount - count);
					} else {
						$('.result').html(allCount);
						$('.need-cdn').html(0);
					}

					// Если все еще есть изображения для сжатия, повторяем запрос
					if ($('.need-cdn').text() !== '0') {
						processImages();
					} else {
						// Заканчиваем процесс
						$('.imagecompresssqueezeimg-blocker').addClass('display-none-imagecompresssqueezeimg');
						$('#start-cdn-compress-image').removeAttr('disabled');
					}
				}).fail(function() {
					alert('Ошибка при обработке запроса. Попробуйте снова.');
					$('.imagecompresssqueezeimg-blocker').addClass('display-none-imagecompresssqueezeimg');
					$('#start-cdn-compress-image').removeAttr('disabled');
				});
			}

			// Инициируем первый вызов функции
			processImages();
		});


		$('#clear-cdn-compress-image').click(function(e){
			e.preventDefault();
			var data = {
				action: 'imagecompresssqueezeimg_cdn_purge_images',
			};

			jQuery.post( ajaxurl, data, function( result ) {
				location.reload();
			});
		});

		$('#save-settings-button').click(function(){
			event.preventDefault();
			var data = {
				action: 'imagecompresssqueezeimg_settings_helper',
				values: $('#imagecompresssqueezeimg-settings-form').serialize()
			};
			$('.imagecompresssqueezeimg-blocker').removeClass('display-none-imagecompresssqueezeimg');

			jQuery.post( ajaxurl, data, function( result ){
				let configs = JSON.parse(result);
				let response = configs.status;
				$('.imagecompresssqueezeimg-blocker').addClass('display-none-imagecompresssqueezeimg');
				let target = '';
				if (response == 'true') {
					target = '.succes-imagecompresssqueezeimg';
					$('#replase-origin-folder-tab').text(configs.replace_origin_images);
				} else {
					target = '.error-imagecompresssqueezeimg';
				}
				if (configs.replace_origin_not_lang) {
					$('.return-original-btn[data-id="jpg"]').fadeOut();
				} else {
					$('.return-original-btn[data-id="jpg"]').fadeIn();
				}
				$(target).removeClass('display-none-imagecompresssqueezeimg');
				setTimeout(() => {  $('.succes-imagecompresssqueezeimg').addClass('display-none-imagecompresssqueezeimg'); }, 2000);
			} );
		});

		$('#create-sitemap-xml').click(function(){
			event.preventDefault();
			var data = {
				action: 'imagecompresssqueezeimg_sitemap_xml'
			};
			jQuery.post( ajaxurl, data, function( result ){
				$('input[name="sitemap"]').val(result);
			} );
		});

		$('#imagecompresssqueezeimg-nav-tab-header a').click(function (event){
			$('#imagecompresssqueezeimg-nav-tab-header a').removeClass('active');
			$(this).addClass('active');
			let target = $(this).attr('data-nav-target');
			$('.imagecompresssqueezeimg-nav-block').addClass('display-none-imagecompresssqueezeimg');
			$('.imagecompresssqueezeimg-nav-block[data-nav-action="' + target + '"]').removeClass('display-none-imagecompresssqueezeimg');
		});

		$('.imagecompresssqueezeimg-radio-mgic').click(function (e){
			// event.preventDefault();
			$(this).toggleClass('active-imagecompresssqueezeimg-radio-mgic');

			let target = $(this).find('input:checked').attr('data-target-switch');

			$(this).find('input[data-state-switch="' + target + '"]').prop('checked', true);
		});

		// crutch for avif
		$(document).on('click', '.imagecompresssqueezeimg-radio-mgic.active-imagecompresssqueezeimg-radio-mgic[data-target-name="convert_images_to_avif_format"]', function (e){
			console.log(1);
			$('.imagecompresssqueezeimg-radio-mgic.active-imagecompresssqueezeimg-radio-mgic[data-target-name="convert_images_to_webp_format"]').trigger('click');
		});
		$(document).on('click', '.imagecompresssqueezeimg-radio-mgic.active-imagecompresssqueezeimg-radio-mgic[data-target-name="convert_images_to_webp_format"]', function (e){
			console.log(1);
			$('.imagecompresssqueezeimg-radio-mgic.active-imagecompresssqueezeimg-radio-mgic[data-target-name="convert_images_to_avif_format"]').trigger('click');
		});

		$('.jamk-media-img-imagecompresssqueezeimg').click(function(e) {
			event.preventDefault();
		});

		$('#input-status_folder').click(function(e){
			$('.imagecompresssqueezeimg-modal-folder').fadeIn();
		});

		$('.imagecompresssqueezeimg-modal-folder-close-button').click(function(e){
			$('.imagecompresssqueezeimg-modal-folder').fadeOut();
		});

		$('.imagecompresssqueezeimg-modal-image-close-button').click(function(e){
			$('.imagecompresssqueezeimg-modal-image').fadeOut();
		});

		var count_send_images_in_request = $('#squeezeimg_count_send_images_in_request').text();
		var squeezeimg_api_token = $('#squeezeimg_api_token').text();

		var PintaCompressAccess = true;
		var compressReped = 0;
		var CompressImages = {
			compress: function (id, page = 1) {
				let rootPath = $('#imagecompresssqueezeimg_root_dir').val();



				$.ajax({
					url: ajaxurl,
					method: 'POST',
					dataType: 'json',
					data: {name: id, page: page, token: squeezeimg_api_token, action: 'imagecompresssqueezeimg_url_compress_igm', root_path: rootPath},
					success: function (res) {

						var block_id = id+'_path';
						var allCount = parseInt($("#" + block_id + " .all-count").html());
						var totalcount = parseInt($('#' + block_id + ' .result').html()) + parseInt($("#"+block_id+" .result-error").html());

						if (allCount > totalcount) {
							if (allCount >= res.count.length) {
								var count = parseInt($('#' + block_id + ' .result').html()) + res.count.length;

								if((count * 100 / allCount) > 100){
									var loader = 100;
								} else {
									var loader = count * 100 / allCount
								}
								if(res != null){
									$('#' + block_id + ' .load-progress').css('width', (loader) + '%');
									if (allCount > count) {
										$('#' + block_id + ' .result').html(count);
									} else {
										$('#' + block_id + ' .result').html(allCount);
									}

								}
								if( (typeof res.error != "undefined") && (res.error.length > 0)){
									$("#"+block_id+" .error").removeClass('hidden');
									$("#"+block_id+" .result-error").html(parseInt($("#"+block_id+" .result-error").html()) +res.error.length);
								}
							}
							var iter = parseInt(allCount / parseInt(count_send_images_in_request));
							iter = iter + 1;


							if (parseInt(res.page) <= iter && $('.compress-convert-btn[data-id="' + id + '"]').hasClass('compressPressed')) {

								if (parseInt(res.page) == iter) {
									if (((allCount - res.count) > 5) && compressReped < 5) {
										page = iter - 1;
										compressReped = compressReped + 1;
									} else {
										page = page + 1;
									}
								} else {
									page = page + 1;
								}
								CompressImages.compress(id, page);
							} else {
								compressReped = 0;
							}
						}

					}

				});
			},

			clearImage: function (id) {

				let rootPath = $('#imagecompresssqueezeimg_root_dir').val();

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					dataType: 'json',
					data: {name: id, action: 'imagecompresssqueezeimg_url_delete_img', root_path: rootPath},
					success: function (res) {
						var block_id = id+'_path';
						if (res.status) {
							$("#" + block_id + " .result").html(0);
							$('#' + block_id + ' .load-progress').css('width', 0 + '%');

						} else {
							let tempclassname = 'cl' + randomInteger(1, 5000);
							$("#" + block_id + " ul").append("<li class='list-group-item error "+tempclassname+"'>" + res.error + "</li>");

							setTimeout(function(){
								$("."+tempclassname).fadeOut();
								setTimeout(function(){
									$("."+tempclassname).remove();

								}, 1000);

							}, 3000);
						}
					}
				});
			},
		}

		jQuery(document).ready(function ($) {

			$('.compress-convert-btn').on('click', function (e) {
				$(this).toggleClass('compressPressed');

				if ($(this).hasClass('compressPressed')) {
					e.preventDefault();
					CompressImages.compress($(this).attr('data-id'));
				}

			})
			$('.return-original-btn').on('click', function (e) {
				e.preventDefault();
				CompressImages.clearImage($(this).attr('data-id'), $(this).attr('data-compress-folder'));
			})

		});
		$(document).on('click', '.list-group-items', function (e) {
			$(this).parent('ul').find('.active-list').each(function () {
				$(this).removeClass('active-list');
			});
			$(this).addClass('active-list');
			let val = $(this).attr('data-value');
			let id = $(this).attr('data-key');
			$(id).val(val);
			if(id == "#format"){
				$('#btn-text').val($('#btn-text').attr('data-'+ val));
			}

		});
		$(document).on('click', '#btn-to-save', function (e) {
			$('.imagecompresssqueezeimg-modal-folder').fadeOut();
			$("#input-status_folder").val($('#check_folder').val());
			$('#btn-text').removeAttr('disabled');
		});
		$(document).on('click', '.list li', function (e) {
			e.stopPropagation();
			var $this = $(this);
			var folder_path = $this.attr('data-path');
			for(let item of document.getElementsByClassName('selected'))
			{
				$(item).removeClass('selected')
				$(item).removeClass('preloader')
			}

			$this.children(".name").addClass('selected');
			$this.children(".name").addClass('preloader');
			$('#check_folder').val(folder_path);

			$.ajax({
				url: ajaxurl,
				method: 'POST',
				type: 'json',
				data: { path: folder_path, action: 'imagecompresssqueezeimg_getFolderTree'},
				success: function (res) {

					for(let item of document.getElementsByClassName('preloader'))
					{
						$(item).removeClass('preloader')
					}
					if (typeof res == 'object') {
						if (res.length > 0) {
							var sub = $this.children('.sub-list');
							if (sub.length > 0) {
								while(sub[0].firstChild){
									sub[0].removeChild(sub[0].firstChild);
								}
								sub[0].innerHTML = '';
								for (let folder of res) {
									let path = folder_path + '/' + folder;
									let li = document.createElement('li');
									li.setAttribute('data-path', path);
									let ul = document.createElement('ul');
									ul.classList.add('sub-list');
									let span = document.createElement('span');
									span.classList.add('icon-folder');
									let span2 = document.createElement('span');
									span2.classList.add('name')
									span2.innerHTML = folder;

									li.append(span);
									li.append(span2);
									li.append(ul);
									sub[0].append(li);
								}
							}
						}
					}
				},
				error:function (err) {
					$('#preloader').addClass('hidden');

				}

			});
			var subList = $(this).children('.sub-list');
			if ($(subList[0]).hasClass('open')) {
				$(this).children().find('.sub-list').removeClass('open');
			} else {
				$(subList[0]).addClass('open');
			}
		});
		$(document).on('click','#btn-text',function (e) {
			e.preventDefault();
			PintaCompressAccess = true;
			let folder = $('#input-status_folder').val();

			$('.imagecompresssqueezeimg-modal-image').fadeIn();
			if(folder != ''){
				let type = $('#format').val();
				let lvl = $('#level').val();
				var page = 1;
				let trs = $('#process-table tr');
				$('#process-table').html(trs[0]);
				console.log(folder,type,lvl,page, trs[0]);
				getImages(folder,type,lvl,page, trs[0]);
			} else {
				e.preventDefault();
			}

		});

		$(document).on('click','.try_compress',function (e) {
			if($(this).attr('disabled') == undefined) {
				$(this).parent().addClass('preloader');
				$('.try_compress').each(function () {
					$(this).attr('disabled', 'disabled');
				});
				let type = $(this).attr('data-id');
				var parent_id = $(this).parents('.block-object-try').attr('id');
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {type: type, token: squeezeimg_api_token, action: 'imagecompresssqueezeimg_try_compress_one_igm'},
					success: function (resp) {

						if (resp.origin && resp.compress) {
							if ($('#img_compress_block').hasClass('hidden')) {
								$('#img_compress_block').removeClass('hidden')
							}
							$('#img_origin').attr('src', resp.origin)
							$('#img_compress').attr('src', resp.compress)
						}
						$('.try_compress').each(function () {
							$(this).removeAttr('disabled');
							if ($(this).parent().hasClass('preloader')) {
								$(this).parent().removeClass('preloader')
							}
						});
						if (resp.error) {

						}
					}
				});
			}
		});

		$(document).on('click','#kill-media-modal-imagecompress',function (e) {
			$('#imagecompress-media-modal').remove();
		});
		$(document).on('click','#close-nginx-attantion-notice',function (e) {
			$('.nginx-attantion-notice').fadeOut();
			$('input[name="ngins_notise_display"]').val('A');

			event.preventDefault();
			var data = {
				action: 'imagecompresssqueezeimg_settings_helper',
				values: $('#imagecompresssqueezeimg-settings-form').serialize()
			};
			$('.imagecompresssqueezeimg-blocker').removeClass('display-none-imagecompresssqueezeimg');

			jQuery.post( ajaxurl, data, function( result ){
				let configs = JSON.parse(result);
				let response = configs.status;
				$('.imagecompresssqueezeimg-blocker').addClass('display-none-imagecompresssqueezeimg');
				let target = '';
				if (response == 'true') {
					target = '.succes-imagecompresssqueezeimg';
					$('#replase-origin-folder-tab').text(configs.replace_origin_images);
				} else {
					target = '.error-imagecompresssqueezeimg';
				}
				if (configs.replace_origin_not_lang) {
					$('.return-original-btn[data-id="jpg"]').fadeOut();
				} else {
					$('.return-original-btn[data-id="jpg"]').fadeIn();
				}
				$(target).removeClass('display-none-imagecompresssqueezeimg');
				setTimeout(() => {  $('.succes-imagecompresssqueezeimg').addClass('display-none-imagecompresssqueezeimg'); }, 2000);
			} );
		});
		$(document).on('click','#imagesProcces',function () {
			let trs = $('#process-table tr');
			$('#process-table').html(trs[0]);
			PintaCompressAccess = false;
		})
		async function  createTables(images,type, lvl, element) {
			if(!PintaCompressAccess){
				return false;
			}
			var table = document.getElementById('process-table');
			for(let image of images){
				if ($('#compressImages').css('display') == 'none') {
					break;
				}

				let tr = document.createElement('tr');
				tr.id = image.name;
				let tdname = document.createElement('td');
				tdname.innerHTML = image.name;
				let tdsizebefore = document.createElement('td');
				tdsizebefore.innerHTML = image.size;

				let tdsizeafter = document.createElement('td');
				tdsizeafter.classList.add("size-after");

				let tdstatus = document.createElement('td');
				let span = document.createElement('span');
				span.classList.add("icon-loader");

				tdstatus.append(span);
				tr.append(tdname);
				tr.append(tdsizebefore);
				tr.append(tdsizeafter);
				tr.append(tdstatus);
				table.append(tr);
				await compressOneImages(tr,image,type, lvl)

			}
		}
		function compressOneImages(tr,image,type, lvl) {
			return new Promise((resolve)=>{
				if(!PintaCompressAccess){
					resolve(false);
				}
				$.ajax({
					url: ajaxurl,
					method: 'POST',
					type: 'json',
					data: { filename: image.filename, type: type, quality: lvl, action: 'imagecompresssqueezeimg_compressOneImg'},
					success: function (res) {

						if(res && res.status){
							$(tr).find('.size-after').html(res.image.size)
							$(tr).find('span').removeClass('icon-loader').addClass('icon-check-ok-image')

						} else {
							$(tr).find('span').removeClass('icon-loader').addClass('icon-cross-image')
						}
						resolve(tr);
					}

				});


			});

		}
		function getImages(folder,type,lvl,page) {
			if(!PintaCompressAccess){
				return false;
			}
			$.ajax({
				url: ajaxurl,
				method: 'POST',
				type: 'json',
				data: { folder: folder, type: type, quality:lvl, page: page, action: 'imagecompresssqueezeimg_getCompressImg', token: squeezeimg_api_token},
				beforeSend: function( xhr ) {
					// $('#preloader').removeClass('hidden');
				},
				success: function (res) {
					if(res && (res.length > 0)){
						page++;
						createTables(res,type, lvl );
						$('#compressImages').animate({ scrollTop: $(document).height() }, 600);
					}
				}
			});
		}

		function randomInteger(min, max) {
			let rand = min - 0.5 + Math.random() * (max - min + 1);
			return Math.round(rand);
		}

	} );

})( jQuery );