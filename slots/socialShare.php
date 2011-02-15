<div class="socialShare cf">
	<div class="sosButton">
		<span class="sosButtonCount"><span><?php echo $post->socialInfo['twitter']['count']; ?></span></span>
		<a href="<?php echo $post->socialInfo['twitter']['shareUrl']; ?>" class="sosTwitter" rel="external nofollow">Twittern</a>
	</div>
	
	<div class="sosButton last">
		<span class="sosButtonCount"><span><?php echo $post->socialInfo['facebook']['count']; ?></span></span>
		<a href="<?php echo $post->socialInfo['facebook']['shareUrl']; ?>" class="sosFacebook" rel="external nofollow">Teilen</a>
	</div>
</div>
