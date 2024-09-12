<!--
 CardDAV Middleware UI
 Written by Massi-X <support@massi-x.dev> © 2024
 This file is protected under CC-BY-NC-ND-4.0, please see "LICENSE" file for more information
-->

<!-- All the js and css is automagically imported by fpbx as long as you symlink the assets folder (view.functions.php) -->
<div class="fpbx-container">
	<div class="display no-border">
		<?php
		//print all the errors (if any)
		if (isset($_POST['errors']) && is_array($_POST['errors'])) {
			echo '<div class="alert alert-danger" role="alert">' . str_replace('%d', count($_POST['errors']), ngettext('You have one error.', 'You have %d errors.', count($_POST['errors']))) . '<ul>';
			foreach ($_POST['errors'] as $error)
				echo "<li>$error</li>";
			echo '</ul></div>';
		}
		?>
		<div class="alert alert-info" role="alert">
			<?php
			if (method_exists(Core::class, 'get_readme_url'))
				echo _('Simple library to retrieve vCards from a CardDAV server and return Inbound CNAM, Outbound CNAM and XML Phonebook.') . ' ' . str_replace('%url', Core::get_readme_url(), _('For detailed instructions and the user manual see here: %url'));
			else
				echo _('Simple library to retrieve vCards from a CardDAV server and return Inbound CNAM, Outbound CNAM and XML Phonebook.');
			?>
		</div>

		<div class="ph-header">
			<div class="title">
				<img class="header-img" src="assets/phonemiddleware/images/<?= file_exists('assets/phonemiddleware/images/icon_core.png') ? 'icon_core.png' : 'icon.png' ?>"> <!-- load custom core icon if present - as in cdr module there is no way to not hardcode this path -->
				<h2>
					<?php
					echo str_replace(
						['%module', '%author'],
						[
							method_exists(Core::class, 'get_module_name') ? Core::get_module_name() : _('CardDAV Middleware UI'),
							(method_exists(Core::class, 'get_author') && !empty(Core::get_author()) && strcmp(Core::get_author(), _('Massi-X')) != 0) ? Core::get_author() . _(' w/ ') : ''
						],
						_('%module by %author')
					);
					echo _('Massi-X'); //always print my name
					?>
				</h2>
			</div>

			<div class="activation">
				<!-- ACTIVATION -->
				<?= method_exists(Core::class, 'get_purchase_buttons') ? Core::get_purchase_buttons() : ''; ?>
			</div>

			<!-- NOTIFICATION UI -->
			<div id="notification-ui">
				<?php
				$notifications = Core::retrieveUINotifications();

				echo '<i class="fa fa-bell" onclick="toggleNotification()" id="notification-header" title="' . _('Notifications') . '"></i><span id="notification-count" data-count="' . count($notifications) . '">…</span>'; //count handled by js
				echo '<div id="notification-container" class="arrow-container">';
				echo '<div class="delete-all"><button onclick="deleteAllNotifications()">' . _('Delete All') . '</button></div>';
				echo '<div class="bubble-container">';

				foreach ($notifications as $notification) {
					$type = 'info';
					switch ($notification['level']) {
						case Core::NOTIFICATION_TYPE_ERROR:
							$type = 'error';
							break;
						case Core::NOTIFICATION_TYPE_VERBOSE:
							$type = 'verbose';
							break;
					}

					//timestamp is converted to locale in js
					echo '<div class="notification-bubble ' . $type . '"><button title="' . _('Delete notification') . '" data-notificationid="' . $notification['ID'] . '" onclick="deleteNotification(this)"><i class="fa fa-times"></i></button><p><span class="notification-timestamp">' . $notification['timestamp'] . '</span>';
					//repetitions
					if ($notification['repeated'] > 1 && $notification['repeated'] < 10)
						echo ' [Repeated ' . $notification['repeated'] . ' times]';
					else if ($notification['repeated'] >= 10)
						echo ' [Repeated many times]';
					//message + close div
					echo '</p>' . $notification['message'] . '</div>';
				}

				echo '</div></div>';
				?>
			</div>
		</div>

		<div class="fpbx-container">
			<form autocomplete="off" name="edit" action="" method="post">
				<div class="display no-border">
					<div class="section" data-id="general">
						<!-- CARDDAV URL -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="carddav_display_url"><?= _('CardDAV Setup'); ?></label>
											</div>
											<div class="col-md-9 col-md-9-flex">
												<input disabled="disabled" type="text" class="form-control form-control-flex" id="carddav_display_url" name="carddav_display_url" value="<?= empty(Core::get_url()) ? _('Not Configured') : Core::get_url() ?>">
												<div class="relative">
													<a href="javascript:;" name="carddav-setup" class="btn btn-danger btn-input-flex" onclick="$('#setupCarddav').dialog('open'); return false;"><?= _('Setup/Change'); ?></a>
													<div class="tips arrow-container" data-tips="2">
														<p><?= _('Then you should setup the module to read data from your server. To do that click "Setup/Change and follow the instructions.'); ?></p>
														<button data-action="next-tip" class="btn fl-right"><?= _('Next Tip'); ?></button>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<!-- OUTPUT CONSTRUCT -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="output_construct"><?= _('CNAM/Phonebook Output'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="output_construct"></i>
												<div class="relative">
													<div class="tips arrow-container" data-tips="3">
														<p><?= str_replace('%simble', '<i class="fa fa-question-circle"></i>', _('Done that, the module is ready to work as it should. You should now tweak the settings per your taste, you can get help for any section by hovering the %simble simble.')); ?></p>
														<button data-action="next-tip" class="btn fl-right"><?= _('Next Tip'); ?></button>
													</div>
												</div>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control notvisible" id="output_construct" name="output_construct" value="<?= Core::get_output_construct() ?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="output_construct-help" class="help-block fpbx-help-block">
										<?= _('This parameter control the appearance of the name printed on incoming and outcoming calls + in the XML phonebook. Defaults to "fn" or Full card name.'); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- OUTPUT LENGTH -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="max_cnam_length"><?= _('Max Characters for CNAM Output'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="max_cnam_length"></i>
											</div>
											<div class="col-md-9">
												<div id="max_cnam_container">
													<label class="ph-checkbox with-border left">
														<input type="checkbox" id="max_cnam_length_enable" name="max_cnam_length_enable" value="on" <?= Core::get_max_cnam_length() == 0 ? '' : 'checked'; ?>>
														<span data-info="custom-checkbox"></span>
													</label>
													<input type="number" class="form-control" id="max_cnam_length" name="max_cnam_length" value="<?= Core::get_max_cnam_length() < 10 ? '10' : Core::get_max_cnam_length(); ?>" min="10" max="200" <?= Core::get_max_cnam_length() == 0 ? 'disabled' : ''; ?>>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="max_cnam_length-help" class="help-block fpbx-help-block">
										<?= _('If the string is too long it may be truncated by the phones you are using. For the name to terminate gracefully, set this property to ellipsis the text at the right spot.'); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- PHONE TYPE -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="phone_type"><?= _('Default Phonebook Type'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="phone_type"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control notvisible" id="phone_type" name="phone_type" value="<?= Core::get_phone_type() ?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="phone_type-help" class="help-block fpbx-help-block">
										<?= str_replace(['%url', '%developer'], [\FreePBX::PhoneMiddleware()->getXmlPhonebookURL(), (method_exists(Core::class, 'get_author') && !empty(Core::get_author())) ? Core::get_author() : 'the developer'], _('Choose the default type for the generated phonebook. Selecting anything different than "UNLIMITED" causes the library to limit the generated output so that your devices can happily read it. You can try with some other manufacturer if yours is not listed, but if this still isn\'t working reach out to %developer.<br>You could also request a specific phonebook via a GET request to the phonebook URL (for example <a target="_blank" href="%url?type=UNLIMITED">%url?type=UNLIMITED</a> replacing "UNLIMITED" with one of the types present in the droplist, without the brackets).')); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- CACHE DURATION -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="cache_expire"><?= _('Cache Duration'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="cache_expire"></i>
											</div>
											<div class="col-md-9">
												<input type="number" class="form-control" id="cache_expire" name="cache_expire" value="<?= Core::get_cache_expire() ?>" max="43200"> <!-- 30 days -->
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="cache_expire-help" class="help-block fpbx-help-block">
										<?= _('Expiration time of the internal cache. Set to "0" to disable <b>(not reccomended)</b>. Max 30 days.'); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- COUNTRY CODE -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="country_code"><?= _('Country Code'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="country_code"></i>
											</div>
											<div class="col-md-9">
												<input type="text" class="form-control notvisible" id="country_code" name="country_code" value="<?= Core::get_country_code() ?>">
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="country_code-help" class="help-block fpbx-help-block">
										<?= str_replace('%url', '<a target="_blank" href="' . _('https://wikipedia.org/wiki/List_of_ISO_3166_country_codes') . '">' . _('ISO format') . '</a>', _('Default country code used for parsing the numbers in %url. This is only used as a fallback if the number contains no country code.')); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- SUPERFECTA COMPATIBILITY -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="superfecta_compat"><?= _('Superfecta Compatibility'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="superfecta_compat"></i>
											</div>
											<div class="col-md-9">
												<span class="radioset">
													<input type="radio" name="superfecta_compat" id="superfecta_OFF" value="off" <?php if (!Core::get_superfecta_compat()) echo 'checked'; ?>>
													<label for="superfecta_OFF" tabindex="0"><?= _('Standard'); ?></label>
													<input type="radio" name="superfecta_compat" id="superfecta_ON" value="on" <?php if (Core::get_superfecta_compat()) echo 'checked'; ?>>
													<label for="superfecta_ON" tabindex="0"><?= _('Full'); ?></label>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="superfecta_compat-help" class="help-block fpbx-help-block">
										<?= str_replace('%module', method_exists(Core::class, 'get_module_name') ? Core::get_module_name() : _('the module'), _('By default %module always returns a result when you request a CNAM to allow giving back useful informations like warning and errors directly on your phone screen. Sadly this is not compatible with other schemes so you can toggle "Full" compatibility and enjoy complete compatibility with multiple Superfecta schemes.')); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- SPAM ENABLE -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="spam_match"><?= _('SPAM Match'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="spam_match"></i>
											</div>
											<div class="col-md-9">
												<span class="radioset">
													<input type="radio" name="spam_match" id="spam_OFF" value="off" <?php if (!Core::get_spam_match()) echo 'checked'; ?>>
													<label for="spam_OFF" tabindex="0"><?= _('OFF'); ?></label>
													<input type="radio" name="spam_match" id="spam_ON" value="on" <?php if (Core::get_spam_match()) echo 'checked'; ?>>
													<label for="spam_ON" tabindex="0"><?= _('ON'); ?></label>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="spam_match-help" class="help-block fpbx-help-block">
										<?= _('Enable matching SPAM calls. To flag a number as SPAM simply create a new category (or tag or label or whatever you call it) in the vCard and set it exactly to "SPAM". Do this for all the contacts you like to create a warning for. Superfecta will also be able to treat calls accordingly.'); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- NOTIFICATION MAIL -->
						<div class="element-container">
							<div class="row">
								<div class="col-md-12">
									<div class="row">
										<div class="form-group">
											<div class="col-md-3 control-label">
												<label for="mail_level[]"><?= _('Notifications via Mail'); ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="mail_level[]"></i>
											</div>
											<div class="col-md-9 flexible">
												<span class="radioset">
													<input type="checkbox" name="mail_level[]" id="notification_info" value="<?= Core::NOTIFICATION_TYPE_INFO; ?>" <?php if (in_array(Core::NOTIFICATION_TYPE_INFO, Core::get_mail_level())) echo 'checked' ?>>
													<label for="notification_info" tabindex="0"><?= _('Info'); ?></label>
													<input type="checkbox" name="mail_level[]" id="notification_error" value="<?= Core::NOTIFICATION_TYPE_ERROR; ?>" <?php if (in_array(Core::NOTIFICATION_TYPE_ERROR, Core::get_mail_level())) echo 'checked' ?>>
													<label for="notification_error" tabindex="0"><?= _('Error'); ?></label>
												</span>
												<span class="item-info">
													<?= 'Email will be sent to: ' . \FreePBX::Phonemiddleware()->getToAddress(); ?>
												</span>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="col-md-12">
									<span id="mail_level[]-help" class="help-block fpbx-help-block">
										<?= _('This sends an email when you receive a notification of the selected importance.<br>The email will be sent to the registered address in "Module Admin", section "Scheduler and Alerts", the "From" address is that of "System Admin" section "Notifications Settings". This requires a well set and working postfix configuration or the use of the commercial "System Admin" module. <b>If something is missing or misconfigured, e-mail won\'t work!</b>'); ?>
									</span>
								</div>
							</div>
						</div>
						<!-- BUTTONS -->
						<div class="element-container flexible">
							<!-- AUTO CONFIG -->
							<div class="link-container">
								<div class="relative">
									<a href="javascript:;" class="btn btn-warning btn-icon" onclick="$('#magicPopup').dialog('open'); return false;"><i class="fa fa-magic"></i><?= _('Auto Configure'); ?></a>
									<div class="tips arrow-container" data-tips="1">
										<p><?= str_replace('%autoconfig', _('Auto Configure'), _('The first thing needed is to setup you PBX system to work nicely with the module. To do that click "%autoconfig" and follow the instructions.')); ?></p>
										<button data-action="next-tip" class="btn fl-right"><?= _('Next Tip'); ?></button>
									</div>
								</div>
								<a href="javascript:;" class="btn btn-success btn-icon" onclick="initTour(); return false;"><i class="fa fa-question"></i><?= _('Open Tutorial'); ?></a>
							</div>
							<!-- SAVE -->
							<input name="submit" type="submit" value="<?= _('Save &amp; Apply'); ?>" class="btn btn-submit">
						</div>
					</div>
				</div>
			</form>
		</div>

		<!-- HELP/CONFIG -->
		<div class="relative">
			<div class="tips arrow-container" data-tips="4">
				<p><?= _('You can find here the URL for the XML Phonebook to be used on your devices. On the side an help section with some common error and fixes.'); ?></p>
				<button data-action="close-tip" class="btn fl-right"><?= _('Got it'); ?></button>
			</div>
			<div class="help-section">
				<a target="_blank" href="<?= \FreePBX::PhoneMiddleware()->getXmlPhonebookURL(); ?>" title="<?= _('Open in new page…'); ?>" class="btn-popup"><?= _('XML phonebook for your device'); ?> <i class="fa fa-external-link"></i></a>
				<a href="javascript:;" class="btn-popup" onclick="$('#errorPopup').dialog('open'); return false;"><?= _('Error codes and fixes'); ?></a>
			</div>
		</div>
		<div class="footer">
			<!-- Website -->
			<b><a href="<?= _('https://massi-x.dev/'); ?>" target="_blank"><?= _('Massi-X DevSite') ?></a></b>
			<!-- Version number -->
			<br><?= str_replace('%version', \Utilities::get_version(), _('Version: %version')); ?>
			&#32;-&#32;
			<!-- (Eventual) Core license -->
			<?php
			try { //if license is provided by core, UI is now only a library. Treat it like that
				$license = Core::get_license_to_display();
				//use '%linkstart' and '%linkend' in core to create link
				echo str_replace(['%linkstart', '%linkend'], ['<a href="javascript:;" onclick="$(\'#licensePopupCore\').dialog(\'open\'); return false;">', '</a>'], $license['description']);
			} catch (\Throwable $t) {
				//core did not provide a license. Show UI one
				echo str_replace('%license', '<a href="javascript:;" onclick="$(\'#licensePopupUI\').dialog(\'open\'); return false;">' . _('CC-BY-NC-ND-4.0') . '</a>', str_replace('%modulename', '<a target="_blank" href="https://github.com/Massi-X/freepbx-carddavmiddleware-ui">' . _('CardDAV Middleware UI') . '</a>', _('%modulename licensed under %license')));
			}
			?>
			&#32;-&#32;
			<!-- Libraries and links -->
			<?= str_replace('%libraries', '<a href="javascript:;" onclick="$(\'#librariesPopup\').dialog(\'open\'); return false;">' . _('libraries') . '</a>', _('Open source %libraries are used')); ?>
			<!-- Custom core -->
			<?php
			try { //load any additional arbitrary thing from core
				$txt = Core::get_additional_footer();
				echo ' - ' . $txt;
			} catch (\Throwable $t) {
			}
			?>
		</div>

		<!-- POPUPS -->
		<div id="welcomePopup" title="<?= str_replace('%module', method_exists(Core::class, 'get_module_name') ? Core::get_module_name() : _('CardDAV Middleware UI'), _('Welcome to %module!')); ?>" style="display: none">
			<?= _('It seems it is the first time you are using the module, would you like to take a quick tour?') ?>
		</div>

		<div id="licensePopupUI" title="<?= str_replace('%modulename', _('CardDAV Middleware UI'), _('%modulename license terms')); ?>" style="display: none">
			<pre><?= file_get_contents(__DIR__ . '/LICENSE'); ?></pre> <!-- This popup is only available if core has not declared his license -->
		</div>

		<div id="licensePopupCore" title="<?= $license['title']; ?>" style="display: none">
			<pre><?= $license['text']; ?></pre>
		</div>

		<div id="librariesPopup" title="<?= _('Open source libraries'); ?>" style="display: none">
			<ul>
				<?php
				if ($license) //if license is provided by core, UI is now only a library. Treat it like that
					echo '<li><a target="_blank" href="https://github.com/Massi-X/freepbx-carddavmiddleware-ui">' . _('CardDAV Middleware UI') . '</a></li>';
				?>

				<li><a target="_blank" href="https://github.com/yairEO/tagify">Tagify</a></li>
				<li><a target="_blank" href="https://github.com/yairEO/dragsort">DragSort</a></li>

				<?php
				try { //display used libraries if provided by core
					foreach (Core::get_libraries_to_display() as $library)
						echo '<li><a target="_blank" href="' . $library['url'] . '">' . $library['name'] . '</a></li>';
				} catch (\Throwable $t) {
				}
				?>
			</ul>
		</div>

		<div id="setupCarddav" title="<?= _('Setup CardDAV Connection'); ?>" style="display: none">
			<form onsubmit="return false;">
				<table id="carddav_parameters">
					<tbody>
						<tr>
							<td>
								<label for="carddav_url"><?= _('Server URL:'); ?></label>
							</td>
							<td class="relative">
								<input autocomplete="off" type="text" id="carddav_url" class="form-control" name="carddav_url" placeholder="<?= _('Empty'); ?>" value="<?= Core::get_url() ?>" />
								<label class="ph-checkbox with-border right" <?= !method_exists(Core::class, 'get_ssl_enabled') ? 'style="display:none;' : ''; ?>>
									<input type="checkbox" id="carddav_ssl_enable" name="carddav_ssl_enable" onchange="toggleSSL(this)" value="on" <?= Core::get_ssl_enabled() ? 'checked' : ''; ?>>
									<span data-info="custom-checkbox"></span>
									<span class="description <?= Core::get_ssl_enabled() ? 'greentext' : 'redtext'; ?>" data-toggled-by="carddav_ssl_enable"><?= Core::get_ssl_enabled() ? _('SSL Active') : _('Bypass SSL'); ?></span>
								</label>
							</td>
							<td>
								<div class="carddav_info small_padding">
									<i class="fa fa-info-circle"></i>
									<i><?= _('Include the protocol (http or https). In case of secure connection make sure the certificate is valid and you are pointing to a domain, not an IP!'); ?></i>
								</div>
							</td>
						</tr>
						<tr>
							<td>
								<label for="carddav_user"><?= _('Username:'); ?></label>
							</td>
							<td>
								<input autocomplete="off" type="text" id="carddav_user" class="form-control" name="carddav_user" placeholder="<?= _('Empty'); ?>" value="<?= Core::get_auth()['username'] ?>" />
							</td>
						</tr>
						<tr>
							<td>
								<label for="carddav_psw"><?= _('Password:'); ?></label>
							</td>
							<td>
								<input autocomplete="off" type="password" id="carddav_psw" class="form-control" name="carddav_psw" placeholder="<?= _('Empty'); ?>" value="<?= Core::get_auth()['password'] ?>" />
							</td>
						</tr>
					</tbody>
				</table>
				<label style="margin-left: 4px;"><?= _('Addressbooks:'); ?></label>
				<div class="carddav_result-tabfix">
					<table id="carddav_result">
						<thead>
							<tr>
								<th><i class="fa fa-arrows"></i></th>
								<th><?= _('Enabled'); ?></th>
								<th><?= _('Name'); ?></th>
								<th><?= _('URL'); ?></th>
							</tr>
						</thead>
						<tbody>
							<!-- filled by js -->
						</tbody>
					</table>
				</div>
				<div class="carddav_info large_padding">
					<i class="fa fa-info-circle"></i>
					<i><?= _('Enable the addressbook(s) you want to use to save the changes. To edit greyed out values disable all the addressbooks first.'); ?></i>
				</div>
				<button type="submit" id="carddav_validate" name="carddav_validate" class="btn" onclick="validateCarddav();">
					<!-- filled by js -->
				</button>
			</form>
		</div>
		<div id="errorPopup" title="<?= _('Error codes and fixes'); ?>" style="display: none">
			<ul>
				<li><?= str_replace('%error', '<span style="color:red">[W!]</span>', _('<b>I see %error in front of the phone number: </b>')) . _('If you see this in front of the phone number and no name, this means that something went terribly wrong while matching the number. Check the notifications for more details.'); ?></li>
				<?php
				if (method_exists(Core::class, 'get_help'))
					foreach (Core::get_help() as $helpArticle)
						echo "<li>$helpArticle</li>";
				?>
			</ul>
		</div>

		<div id="magicPopup" title="<?= _('Automatic Configuration'); ?>" style="display: none">
			<form onsubmit="return false;">
				<p>
					<?= str_replace('%module', method_exists(Core::class, 'get_module_name') ? Core::get_module_name() : _('the module'), _('<b>Welcome!</b> This automatic procedure will help you to setup your FreePBX installation correctly so that you can start using %module right away. Please note that this procedure relies on private APIs and can fail at any time.')); ?>
					<br><?= _('Before continue you need to be aware of the following:'); ?>
				</p>
				<ul class="numeric-list">
					<li><?= str_replace('%scheme', \Utilities::SUPERFECTA_SCHEME, _('A new Superfecta scheme called "%scheme" will be created and set as master (you are advised not to change the name so script automations can work correctly)')); ?></li>
					<li><?= _('OutCNAM settings will be changed'); ?></li>
					<li><?= _('Inbound routes setup will be changed to enable matching on the new superfecta scheme'); ?></li>
					<li><?= _('Inbound routes CID Lookup will be disabled'); ?></li>
				</ul>

				<pre id="magic_pre_container">
					<?= _('Waiting to start...'); ?>
				</pre>

				<details>
					<summary><b><?= _('Manual configuration'); ?></b></summary>
					<p><?= _('To manually setup your system you will first need to configure Superfecta as follows, then enable OutCNAM and lastly configure your Inbound routes'); ?></p>
					<ul class="numeric-list">
						<li><?= str_replace('%scheme', \Utilities::SUPERFECTA_SCHEME, _('Create a new scheme and call it "%scheme" (important to make sure the script handles automations correctly!)')); ?></li>
						<li><?= _('Set Lookup timeout to <u>5</u> (it is usually enough)'); ?></li>
						<li><?= _('Set Superfecta Processor to <u>SINGLE</u>'); ?></li>
						<li><?= _('You can leave the other values at their default'); ?></li>
						<li><?= _('OPTIONAL: If you need SPAM Interception enable it, set "SPAM Send Threshold" to "1" and choose what to do in case of a match'); ?></li>
						<li><?= _('Save the scheme and enter the edit inferface'); ?></li>
						<li><?= _('Set <u>Regular Expressions 2</u> in <u>Data Source Name</u> column to <u>Yes</u> and disable everything else'); ?></li>
						<li><?= _('Enter the edit interface of <u>Regular Expressions 2</u>'); ?></li>
						<li>
							<span><?= _('Set <u>URL</u> to:'); ?>&nbsp;</span>
							<!-- This is from superfecta, it does not have a translation -->
							<input type="text" readonly="true" onfocus="this.setSelectionRange(0, this.value.length)" class="autoselect_container" value="<?= \FreePBX::PhoneMiddleware()->getNumberToCnamURL(); ?>">
							<?php
							if (substr(\FreePBX::PhoneMiddleware()->getNumberToCnamURL(), 0, 5) === 'https')
								echo '<b class="redtext">' . _('Caution! SSL detected, your system must have a valid FQDN and certificate or superfecta lookup will fail!') . '</b>';
							?>
						</li>
						<li>
							<span><?= _('Set <u>POST Data</u> to:'); ?>&nbsp;</span>
							<!-- This is from superfecta, it does not have a translation -->
							<input type="text" readonly="true" onfocus="this.setSelectionRange(0, this.value.length)" class="autoselect_container" value="<?= htmlentities(\Utilities::SUPERFECTA_SCHEME_CONFIG['POST_Data']); ?>">
						</li>
						<li>
							<span><?= _('Set <u>Regular Expressions</u> to:'); ?>&nbsp;</span>
							<!-- This is from superfecta, it does not have a translation -->
							<input type="text" readonly="true" onfocus="this.setSelectionRange(0, this.value.length)" class="autoselect_container" value="<?= htmlentities(\Utilities::SUPERFECTA_SCHEME_CONFIG['Regular_Expressions']); ?>">
						</li>
						<li><?= _('Enable "SPAM Match"'); ?></li>
						<li>
							<span><?= _('Set <u>SPAM Regular Expressions</u> to:'); ?>&nbsp;</span>
							<!-- This is from superfecta, it does not have a translation -->
							<input type="text" readonly="true" onfocus="this.setSelectionRange(0, this.value.length)" class="autoselect_container" value="<?= htmlentities(\Utilities::SUPERFECTA_SCHEME_CONFIG['SPAM_Regular_Expressions']); ?>">
						</li>
						<li><?= _('Save the expression'); ?></li>
						<li><?= _('Go to OutCNAM, enable all the options and set the scheme to the newly created one'); ?></li>
						<li><?= _('Go to your Inbound route(s), "Other" tab') ?></li>
						<li><?= _('Disable "CID Lookup Source"') ?></li>
						<li><?= _('Check "Enable Superfecta Lookup", then set "Superfecta Scheme" to the newly created one'); ?></li>
						<li><?= _('Repeat these steps for every inbound route you have'); ?></li>
						<li><?= _('At last, apply the changes'); ?></li>
					</ul>
				</details>
				</p>
				<button id="magic_start" class="btn" onclick="new MagicConfig(magicPopup, pm_language)"><?= _('Start'); ?></button>
			</form>
		</div>
		<!-- END POPUPS -->
	</div>
</div>