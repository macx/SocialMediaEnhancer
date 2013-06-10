<?php
	if(is_object($this)) {
		$sme = $this;
	}
?>
<section class="smeShare<?php if($sme->options['general']['style'] == 'dark') echo ' smeDark'; ?>">
	<?php if(in_array($sme->options['general']['style'], array('sme', 'flat'))): ?>
		<?php $smeButtonStyle = ($sme->options['general']['style'] == 'sme') ? 'smeBtn': 'smeFlat'; ?>
		<p>
			<?php if($sme->options['general']['services']['google'] == 1): ?>
				<a href="<?php echo $post->socialInfo['google']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Google" rel="sme" data-service="google"><i></i><?php echo $post->socialInfo['google']['count']; ?></a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['facebook'] == 1): ?>
				<a href="<?php echo $post->socialInfo['facebook']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Facebook" rel="sme" data-service="facebook"><i></i><?php echo $post->socialInfo['facebook']['count']; ?></a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['twitter'] == 1): ?>
				<a href="<?php echo $post->socialInfo['twitter']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Twitter" rel="sme" data-service="twitter"><i></i><?php echo $post->socialInfo['twitter']['count']; ?></a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['pinterest'] == 1): ?>
				<a href="<?php echo $post->socialInfo['pinterest']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Pinterest" rel="sme" data-service="pinterest"><i></i><?php echo $post->socialInfo['pinterest']['count']; ?></a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['linkedin'] == 1): ?>
				<a href="<?php echo $post->socialInfo['linkedin']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Linkedin" rel="sme" data-service="linkedin"><i></i><?php echo $post->socialInfo['linkedin']['count']; ?></a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['xing'] == 1): ?>
				<a href="<?php echo $post->socialInfo['xing']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Xing" rel="sme" data-service="xing"><i></i><?php echo $post->socialInfo['xing']['count']; ?></a>
			<?php endif; ?>
		</p>
	<?php else: ?>
		<?php if($sme->options['general']['services']['google'] == 1): ?>
			<a href="<?php echo $post->socialInfo['google']['shareUrl']; ?>" class="smeClassicBtnGoogle" rel="sme nofollow" data-service="google">
				<b>+1</b>
				<i><span><?php echo $post->socialInfo['google']['count']; ?></span></i>
			</a>
		<?php endif; ?>

		<?php if($sme->options['general']['services']['facebook'] == 1): ?>
			<a href="<?php echo $post->socialInfo['facebook']['shareUrl']; ?>" class="smeClassicBtnFacebook" rel="sme nofollow" data-service="facebook">
				<b>Teilen</b>
				<i><span><?php echo $post->socialInfo['facebook']['count']; ?></span></i>
			</a>
		<?php endif; ?>

		<?php if($sme->options['general']['services']['twitter'] == 1): ?>
			<a href="<?php echo $post->socialInfo['twitter']['shareUrl']; ?>" class="smeClassicBtnTwitter" rel="sme nofollow" data-service="twitter">
				<b>Twittern</b>
				<i><span><?php echo $post->socialInfo['twitter']['count']; ?></span></i>
			</a>
		<?php endif; ?>
	<?php endif; ?>
</section>
