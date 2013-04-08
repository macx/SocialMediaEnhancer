<div class="socialBar clear cf">
	<div class="sbButton">
		<a href="<?php comments_link(); ?>" class="sbComments" rel="nofollow">Kommentieren</a>
		<span class="sbButtonCount"><span><?php comments_number('0', '1', '%'); ?></span></span>
	</div>

	<div class="sbButton">
		<a href="<?php echo $post->socialInfo['twitter']['shareUrl']; ?>" class="sbTwitter" rel="external nofollow">Twittern</a>
		<span class="sbButtonCount"><span><?php echo $post->socialInfo['twitter']['count']; ?></span></span>
	</div>

	<div class="sbButton">
		<a href="<?php echo $post->socialInfo['facebook']['shareUrl']; ?>" class="sbFacebook" rel="external nofollow">Teilen</a>
		<span class="sbButtonCount"><span><?php echo $post->socialInfo['facebook']['count']; ?></span></span>
	</div>

	<div class="sbMeta">
		<?php if(!is_home()) { the_time('d.m.Y'); } ?>
		<?php edit_post_link('bearbeiten', ' &nbsp; <span class="gray">', '</span>'); ?>
	</div>
</div>
