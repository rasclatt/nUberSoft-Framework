<?php
$prefs	=	$this->getDataNode();
?>
			<noscript>
			<style>
				.login_container	{ display: none; }
				.needJS				{ display: block; }
			</style>
			</noscript>
			<div class="needJS">
			<h2>Javascript Error!</h2>
			<p>You are required to have Javascript turned on.</p>
			</div>
			<div class="login_container">
            	<div class="login_bkg">
					<?php echo $this->getPlugin('\nPlugins\Nubersoft\View')->renderSiteLogo(array("id"=>"nbr_login_logo")) ?>
                    <div id="login">
                        <form method="post" action="#" enctype="application/x-www-form-urlencoded" id="loginForm">
							<div class="login_fields"><input type="text"  id="username" name="username" autocomplete="off" required placeholder="Username" /></div>
							<div class="login_fields"><input type="password" id="password" name="password" autocomplete="off" required placeholder="Password" /></div>
							<input type="hidden" id="action" name="action" value="login" />
							<input type="hidden" name="token[login]" />
                            <div class="nbr_contain">
	                                <div class="nbr_button"><input disabled="disabled" type="submit" name="login" value="Login" id="loginsubmit" class="disabled-submit" /></div>
							</div>
                        </form>
						<?php
							if($this->siteValid())
								$turn_on_signup	=	(isset($prefs->preferences->site->content->sign_up->toggle) && $prefs->preferences->site->content->sign_up->toggle == 'on')? true:false;
							
							$turn_on_signup	=	(!isset($turn_on_signup	))? false:$turn_on_signup;
							if($turn_on_signup) {
?>
                            <p class="after_text">Not a member? <span class="after_link" onClick="MM_changeProp('sign_up','','display','inline-block');MM_changeProp('login','','display','none')">Sign up now!</span></p>
<?php							}
?>
                    	</div>
<?php						if($turn_on_signup) {
?>
                        <div id="sign_up" style="display: none;">
                            <form method="post" action="" enctype="application/x-www-form-urlencoded" id="signupForm">
                            	<input type="hidden" name="errors" value="on" />
                                	<label for="first_name">First Name</label>	
                                    <div class="login_fields"><input type="text" name="first_name" id="first_name" style="width: 98%;" class="no-paste" /></div>
                                    <label for="last_name">Last Name</label>
                                    <div class="login_fields"><input type="text" name="last_name" id="last_name" style="width: 98%;" class="no-paste" /></div>
                                    <label for="email">Email</label>
                                    <div class="login_fields"><input type="text" name="email" id="email" style="width: 98%;" class="no-paste usercheck"  /></div>
                                    <label for="username">Username</label>
                                    <div class="login_fields"><input type="text" name="username" id="username" style="width: 98%;" class="no-paste usercheck" /></div>
                                    <label for="password">Password</label>
                                    <div class="login_fields"><input type="password" name="password" id="spassword" style="width: 98%;" class="no-paste usercheck" /></div>
                                    <label for="confirm_password">Confirm Password</label>
                                    <div class="login_fields"><input type="password" name="confirm_password" id="confirm_password" style="width: 98%;" class="no-paste" /></div>
                                <input type="hidden" name="action" value="sign_up" />
								<div class="nbr_contain">
	                                <div class="nbr_button"><input disabled="disabled" type="submit" name="add" value="Sign Up" id="sign_up_button" class="disabled-submit" /></div>
								</div>
                            </form>
							<div class="nbr_contain">
								<div id="use-error-block" class="nbr_invalid_msg">
									<i>Invalid username or email. You may have already signed up with these credentials.</i>
								</div>
								<div id="scriptor"></div>
								<p class="after_text">Already a member? <span class="after_link" onClick="MM_changeProp('sign_up','','display','none');MM_changeProp('login','','display','inline-block')">Log in now!</span></p>
							</div>
                        </div>
<?php							}
							
							if(!empty($error['general']))
								echo $error['general'];
?>
						<?php echo $this->useTemplatePlugin('incidentals','login_window.php') ?>
                        <div class="nbr_contain">
                            <p class="smaller_print">Log in above or go to <a href="<?php echo $this->siteUrl() ?>">Home.</a></p>
                        </div>
					</div>
                </div>
<script>
$(document).ready(function() {
	$('.nbr_error_msg_block').fadeIn('slow').delay(5000).fadeOut();
});
</script>