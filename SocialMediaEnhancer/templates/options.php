<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2><?php echo __('Einstellungen › SocialMediaEnhancer'); ?></h2>

	<p>Der SocialMediaEnhancer erweitert dein Blog durch Social-Media-Icons, zählt die Shares und ermöglicht es deinen Besuchern, deine Inhalte leicht
	zu teilen.</p>

	<p><b>Wichtig!</b> Die Shares werden maximal einmal in der Stunde pro aufgerufenen Artikel abgefragt. Durch Speichern des Artikels in WordPress können
	Sie eine neue Zählung veranlassen.</p>

	<p>Die Daten ses SME stehen im Array zur Verfügung. <code>&lt;?php echo $post->socialInfo['twitter']['count']; ?&gt;</code> zeigt die Anzahl von Tweets an,
	<code>&lt;?php echo $post->socialInfo['total']; ?&gt;</code> zeigt alle sozialen Aktivitäten inklusive aller Kommentare an.</p>

	<form action="options.php" method="post">
		<?php
			settings_fields('smeOptions');

			do_settings_sections('options');

			#do_settings_fields('smeAccounts', 'twitterUsername');
			#do_settings_fields('smeButtons', 'style');
		?>

		<h3>Allgemeine Einstellungen</h3>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">Anzuzeigende Dienste</th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><span>Social-Media-Dienste</span></legend>

							<label for="service_google">
								<input name="smeOptions[general][services][google]" type="checkbox" id="service_google" value="1"<?php if($this->options['general']['services']['google'] == 1) echo ' checked="checked"'; ?>>
								Google+
							</label><br>

							<label for="service_facebook">
								<input name="smeOptions[general][services][facebook]" type="checkbox" id="service_facebook" value="1"<?php if($this->options['general']['services']['facebook'] == 1) echo ' checked="checked"'; ?>>
								Facebook+
							</label><br>

							<label for="service_twitter">
								<input name="smeOptions[general][services][twitter]" type="checkbox" id="service_twitter" value="1"<?php if($this->options['general']['services']['twitter'] == 1) echo ' checked="checked"'; ?>>
								Twitter
							</label><br>

							<label for="service_linkedin">
								<input name="smeOptions[general][services][linkedin]" type="checkbox" id="service_linkedin" value="1"<?php if($this->options['general']['services']['linkedin'] == 1) echo ' checked="checked"'; ?>>
								LinkedIn
							</label><br>

							<label for="service_pinterest">
								<input name="smeOptions[general][services][pinterest]" type="checkbox" id="service_pinterest" value="1"<?php if($this->options['general']['services']['pinterest'] == 1) echo ' checked="checked"'; ?>>
								Pinterest
							</label><br>

              <label for="service_pinterest">
                <input name="smeOptions[general][services][xing]" type="checkbox" id="service_xing" value="1"<?php if($this->options['general']['services']['xing'] == 1) echo ' checked="checked"'; ?>>
                XING
              </label><br>

							<p class="description">Von jedem aktivierten Dienst wird der Teilen-Button auf auf der Webseite angezeigt. Zusätzlich
							werden von dem jeweiligen Dienst die aktuellen Zahlen abgerufen.</p>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Button-Design</th>
					<td>
						<select name="smeOptions[general][style]" id="default_post_format">
							<option value="sme"<?php if($this->options['general']['style'] == 'css') echo ' selected="selected"'; ?>>Buttons</option>
							<option value="flat"<?php if($this->options['general']['style'] == 'flat') echo ' selected="selected"'; ?>>Flat-Design</option>
							<option value="light"<?php if($this->options['general']['style'] == 'light') echo ' selected="selected"'; ?>>Klassisch (für helle Hintergründe)</option>
							<option value="dark"<?php if($this->options['general']['style'] == 'dark') echo ' selected="selected"'; ?>>Klassisch (für dunkele Hintergründe)</option>
						</select><br>

						<p class="description">Bestimmt das Aussehen der Buttons.</p>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Platzierung der Buttons</th>
					<td>
						<select name="smeOptions[general][embed]" id="default_post_format">
							<option value="begin"<?php if($this->options['general']['embed'] == 'begin') echo ' selected="selected"'; ?>>Am Anfang jedes Artikels (empfohlen)</option>
							<option value="end"<?php if($this->options['general']['embed'] == 'end') echo ' selected="selected"'; ?>>Am Ende jedes Artikels</option>
							<option value="disabled"<?php if($this->options['general']['embed'] == 'disabled') echo ' selected="selected"'; ?>>Manuelle Einbindung</option>
						</select><br>

						<p class="description">Wenn Sie manuelle Einbindung wählen, fügen Sie den Marker <code>[socialMediaEnhancer]</code> in den
						Quelltext ein.</p>
					</td>
				</tr>
			</tbody>
		</table>

		<br>

		<h3>Social-Media-Accounts</h3>

		<p>Tragen Sie bitte hier Ihre Social-Media-Account ein.</p>

		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						Google+<br>
						<small>(Vollständige URL)</small>
					</th>
					<td>
						<input type="text" name="smeOptions[accounts][google]" value="<?php echo $this->options['accounts']['google']; ?>" class="regular-text ltr" placeholder="https://plus.google.com/102458928073783517690" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						Facebook<br>
						<small>(Vollständige URL)</small>
					</th>
					<td>
						<input type="text" name="smeOptions[accounts][facebook]" value="<?php echo $this->options['accounts']['facebook']; ?>" class="regular-text ltr" placeholder="https://www.facebook.com/dmacx" />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">
						Twitter-Username
						<small>(without @)</small>
					</th>
					<td>
						<input type="text" name="smeOptions[accounts][twitter]" value="<?php echo $this->options['accounts']['twitter']; ?>" class="regular-text ltr" placeholder="macx" />
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>
	</form>
</div>

