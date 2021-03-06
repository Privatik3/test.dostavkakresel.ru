<?php echo $header; ?>
<div class="container">
	<ul class="breadcrumb <?php if (in_array('information/news', $menu_schema)) { ?>col-md-offset-4 col-lg-offset-3<?php } ?>">
		<?php foreach ($breadcrumbs as $i=> $breadcrumb) { ?>
			<?php if($i+1<count($breadcrumbs)) { ?><li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li><?php } else { ?><li><?php echo $breadcrumb['text']; ?></li><?php } ?>
		<?php } ?>
	</ul>
	<div class="row"><?php echo $column_left; ?>
		<?php if ($column_left && $column_right) { ?>
			<?php $class = 'col-sm-4 col-md-6'; ?>
		<?php } elseif ($column_left || $column_right) { ?>
			<?php $class = 'col-sm-8 col-md-8 col-lg-9'; ?>
		<?php } else { ?>
			<?php $class = 'col-sm-12'; ?>
		<?php } ?>
		<?php if (in_array('information/news', $menu_schema) && !$column_left && $column_right) { $class = 'col-sm-8 col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-3'; } ?>
		<?php if (in_array('information/news', $menu_schema) && !$column_left && !$column_right) { $class = 'col-sm-8 col-md-8 col-lg-9 col-md-offset-4 col-lg-offset-3'; } ?>
		<div id="content" class="<?php echo $class; ?>"><?php echo $content_top; ?>
			<h1 class="heading"><span><?php echo $heading_title; ?></span></h1>
			<?php if (isset($news_info)) { ?>
				<div class="news_page">
					<div>
						<?php if ($image) { ?>
							<div class="image"><a href="<?php echo $popup; ?>" title="<?php echo $heading_title; ?>" class="img_popup"><img src="<?php echo $thumb; ?>" alt="<?php echo $heading_title; ?>" id="image" class="img-responsive" /></a></div>
						<?php } ?>
						<div class="description"><?php echo $description; ?></div>
					</div>
					<div class="show_all_news">
						<?php if ($addthis) { ?>
							<div class="col-xs-6 text-left">
								<script type="text/javascript" src="//yastatic.net/es5-shims/0.0.2/es5-shims.min.js" charset="utf-8"></script>
								<script type="text/javascript" src="//yastatic.net/share2/share.js" charset="utf-8"></script>
								<div class="ya-share2" data-services="vkontakte,facebook,odnoklassniki,gplus,twitter,viber,whatsapp" data-counter=""></div>
							</div>
						<?php } ?>
						<div class="<?php if ($addthis) { ?>col-xs-6 text-right<?php } else { ?>col-xs-12<?php } ?>">
							<a onclick="location='<?php echo $news; ?>'" ><?php echo $button_news; ?></a>
						</div>
					</div>
				</div>
<script>
	$(document).ready(function() {
		$('.news_page .description img').each(function() {
			var href = $(this).attr('src');
			$(this).addClass('img-responsive').attr('itemprop', 'image');
			$(this).wrap('<a href="'+href+'" class="img_popup" style="max-width:350px; overflow:auto;"></a>');
		});
		$('.news_page .image, .news_page .description').magnificPopup({
			type:'image',
			delegate: 'a.img_popup',
			gallery: {
				enabled:true
			}
	});
});
</script>
			<?php } elseif (isset($news_data)) { ?>
				<div class="news_list">
					<?php foreach ($news_data as $news) { ?>
							<div class="image_description row">
								<div class="image col-xs-12 col-sm-4 col-md-2"><img src="<?php echo $news['image']; ?>" alt="<?php echo $news['title']; ?>" class="img-responsive" /></div>
								<div style="margin:0 0 10px" class="col-xs-12 visible-xs"></div>
								<div class="col-xs-12 col-sm-8 col-md-10">
									<h4><?php echo $news['title']; ?></h4>
									<div class="description"><?php echo $news['description']; ?></div>
									<div class="row">
										<div class="col-xs-6"><a href="<?php echo $news['href']; ?>"><?php echo $text_more; ?></a></div>
										<div class="posted col-xs-6"><?php echo $news['posted']; ?></div>
									</div>
								</div>
							</div>
						<hr />
					<?php } ?>
				</div>
				<?php echo $pagination; ?>
			<?php } ?>
			<?php echo $content_bottom; ?>
		</div>
		<?php echo $column_right; ?>
	</div>
</div>
<?php echo $footer; ?>