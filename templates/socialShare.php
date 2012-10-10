<section class="socialShare">
	<?php if($this->options['general']['services']['twitter'] == 1): ?>
		<a href="<?php echo $post->socialInfo['twitter']['shareUrl']; ?>" class="scButton scTwitter" rel="popup nofollow">
			<b>Twittern</b>
			<i><span><?php echo $post->socialInfo['twitter']['count']; ?></span></i>
		</a>
	<?php endif; ?>

	<?php if($this->options['general']['services']['facebook'] == 1): ?>
		<a href="<?php echo $post->socialInfo['facebook']['shareUrl']; ?>" class="scButton scFacebook" rel="popup nofollow">
			<b>Teilen</b>
			<i><span><?php echo $post->socialInfo['facebook']['count']; ?></span></i>
		</a>
	<?php endif; ?>

	<?php if($this->options['general']['services']['google'] == 1): ?>
		<a href="<?php echo $post->socialInfo['googleplus']['shareUrl']; ?>" class="scButton scGooglePlus" rel="popup nofollow">
			<b>+1</b>
			<i><span><?php echo $post->socialInfo['googleplus']['count']; ?></span></i>
		</a>
	<?php endif; ?>
</section>