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
				<a href="<?php echo $post->socialInfo['google']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Google" rel="sme nofollow" data-service="google">
					<i></i>
					<?php if($sme->options['general']['label'] == 1): ?>Google+<?php endif; ?>
					<span><?php echo $post->socialInfo['google']['count']; ?></span>
				</a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['facebook'] == 1): ?>
				<a href="<?php echo $post->socialInfo['facebook']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Facebook" rel="sme nofollow" data-service="facebook">
					<i></i>
					<?php if($sme->options['general']['label'] == 1): ?>Facebook<?php endif; ?>
					<span><?php echo $post->socialInfo['facebook']['count']; ?></span>
				</a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['twitter'] == 1): ?>
				<a href="<?php echo $post->socialInfo['twitter']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Twitter" rel="sme nofollow" data-service="twitter">
					<i></i>
					<?php if($sme->options['general']['label'] == 1): ?>Twitter<?php endif; ?>
					<span><?php echo $post->socialInfo['twitter']['count']; ?></span>
				</a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['pinterest'] == 1): ?>
				<a href="<?php echo $post->socialInfo['pinterest']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Pinterest" rel="sme nofollow" data-service="pinterest">
					<i></i>
					<?php if($sme->options['general']['label'] == 1): ?>Pinterest<?php endif; ?>
					<span><?php echo $post->socialInfo['pinterest']['count']; ?></span>
				</a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['linkedin'] == 1): ?>
				<a href="<?php echo $post->socialInfo['linkedin']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Linkedin" rel="sme nofollow" data-service="linkedin">
					<i></i>
					<?php if($sme->options['general']['label'] == 1): ?>LinkedIn<?php endif; ?>
					<span><?php echo $post->socialInfo['linkedin']['count']; ?></span>
				</a>
			<?php endif; ?>
			<?php if($sme->options['general']['services']['xing'] == 1): ?>
				<a href="<?php echo $post->socialInfo['xing']['shareUrl']; ?>" class="<?php echo $smeButtonStyle; ?>Xing" rel="sme nofollow" data-service="xing">
					<i></i>
					<?php if($sme->options['general']['label'] == 1): ?>XING<?php endif; ?>
					<span><?php echo $post->socialInfo['xing']['count']; ?></span>
				</a>
			<?php endif; ?>
		</p>
	<?php else: ?>
		<?php if($sme->options['general']['services']['google'] == 1): ?>
			<a href="<?php echo $post->socialInfo['google']['shareUrl']; ?>" class="smeClassicBtnGoogle" rel="sme nofollow" data-service="google">
				<b>Google+</b>
				<i><span><?php echo $post->socialInfo['google']['count']; ?></span></i>
			</a>
		<?php endif; ?>

		<?php if($sme->options['general']['services']['facebook'] == 1): ?>
			<a href="<?php echo $post->socialInfo['facebook']['shareUrl']; ?>" class="smeClassicBtnFacebook" rel="sme nofollow" data-service="facebook">
				<b>Facebook</b>
				<i><span><?php echo $post->socialInfo['facebook']['count']; ?></span></i>
			</a>
		<?php endif; ?>

		<?php if($sme->options['general']['services']['twitter'] == 1): ?>
			<a href="<?php echo $post->socialInfo['twitter']['shareUrl']; ?>" class="smeClassicBtnTwitter" rel="sme nofollow" data-service="twitter">
				<b>Twitter</b>
				<i><span><?php echo $post->socialInfo['twitter']['count']; ?></span></i>
			</a>
		<?php endif; ?>
	<?php endif; ?>
</section>
