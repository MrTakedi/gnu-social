<?php
// This file is part of GNU social - https://www.gnu.org/software/social
//
// GNU social is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// GNU social is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with GNU social.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Web Installer
 *
 * @package   Installation
 * @author    Adrian Lang <mail@adrianlang.de>
 * @author    Brenda Wallace <shiny@cpan.org>
 * @author    Brett Taylor <brett@webfroot.co.nz>
 * @author    Brion Vibber <brion@pobox.com>
 * @author    CiaranG <ciaran@ciarang.com>
 * @author    Craig Andrews <candrews@integralblue.com>
 * @author    Eric Helgeson <helfire@Erics-MBP.local>
 * @author    Evan Prodromou <evan@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @author    Robin Millette <millette@controlyourself.ca>
 * @author    Sarven Capadisli <csarven@status.net>
 * @author    Tom Adams <tom@holizz.com>
 * @author    Zach Copley <zach@status.net>
 * @author    Diogo Cordeiro <diogo@fc.up.pt>
 * @author    Kim <kim@aficat.com>
 * @copyright 2019 Free Software Foundation, Inc http://www.fsf.org
 * @license   https://www.gnu.org/licenses/agpl.html GNU AGPL v3 or later
 */

define('INSTALLDIR', dirname(__DIR__));
define('PUBLICDIR', INSTALLDIR . DIRECTORY_SEPARATOR . 'public');

require INSTALLDIR . '/lib/installer.php';

/**
 * Helper class for building form
 */
class Posted
{
    /**
     * HTML-friendly escaped string for the POST param of given name, or empty.
     * @param string $name
     * @return string
     */
    public function value(string $name): string
    {
        return htmlspecialchars($this->string($name));
    }

    /**
     * The given POST parameter value, forced to a string.
     * Missing value will give ''.
     *
     * @param string $name
     * @return string
     */
    public function string(string $name): string
    {
        return strval($this->raw($name));
    }

    /**
     * The given POST parameter value, in its original form.
     * Magic quotes are stripped, if provided.
     * Missing value will give null.
     *
     * @param string $name
     * @return mixed
     */
    public function raw(string $name)
    {
        if (isset($_POST[$name])) {
            return $this->dequote($_POST[$name]);
        } else {
            return null;
        }
    }

    /**
     * If necessary, strip magic quotes from the given value.
     *
     * @param mixed $val
     * @return mixed
     */
    public function dequote($val)
    {
        if (get_magic_quotes_gpc()) {
            if (is_string($val)) {
                return stripslashes($val);
            } elseif (is_array($val)) {
                return array_map([$this, 'dequote'], $val);
            }
        }
        return $val;
    }
}

/**
 * Web-based installer: provides a form and such.
 */
class WebInstaller extends Installer
{
    /**
     * the actual installation.
     * If call libraries are present, then install
     *
     * @return void
     */
    public function main()
    {
        if (!$this->checkPrereqs()) {
            $this->warning(_('Please fix the above stated problems and refresh this page to continue installing.'));
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->handlePost();
        } else {
            $this->showForm();
        }
    }

    /**
     * Web implementation of warning output
     * @param string $message
     * @param string $submessage
     */
    public function warning(string $message, string $submessage = '')
    {
        print "<div class=\"alert alert-danger\" role=\"alert\">$message";
        if ($submessage != '') {
            print "<hr><p class=\"mb-0\">$submessage</p>\n";
        }
        print "</div>\n";
    }

    /**
     * Web implementation of status output
     * @param string $status
     * @param bool $error
     */
    public function updateStatus(string $status, bool $error = false)
    {
        print "<div class=\"alert alert-". ($error ? 'danger': 'info' ) ."\" role=\"alert\">";
        if ($status != '') {
            print "<hr><p class=\"mb-0\">$status</p>\n";
        }
        print "</div>\n";
    }

    /**
     * Show the web form!
     */
    public function showForm()
    {
        global $dbModules;
        $post = new Posted();
        $dbRadios = '';
        $dbtype = $post->raw('dbtype');
        foreach (self::$dbModules as $type => $info) {
            if (extension_loaded($info['check_module'])) {
                if ($dbtype == null || $dbtype == $type) {
                    $checked = 'checked="checked" ';
                    $dbtype = $type; // if we didn't have one checked, hit the first
                } else {
                    $checked = '';
                }
                $dbRadios .= sprintf('<div class="custom-control custom-switch"><input type="radio" name="dbtype" id="dbtype-%1$s" value="%1$s" class="custom-control-input" %2$s/><label class="custom-control-label" for="dbtype-mysql>%3$s</label></div>',
                    htmlspecialchars($type),
                    $checked,
                    htmlspecialchars($info['name'])
                );
            }
        }

        $ssl = ['always' => null, 'never' => null];
        if (!empty($_SERVER['HTTPS'])) {
            $ssl['always'] = 'checked="checked"';
        } else {
            $ssl['never'] = 'checked="checked"';
        }

        echo <<<E_O_T
    <form method="post" action="install.php" class="form_settings" id="form_install">
    	<legend>Site settings</legend>
    	<hr>
        <div class="form-group">
            <label for="sitename">Site name</label>
            <input type="text" id="sitename" name="sitename" class="form-control" value="{$post->value('sitename')}" />
            <small class="form-text text-muted">The name of your site</small>
        </div>
        <div class="form-row">
        	<div class="col-12 col-sm-6">
		    	<label for="fancy">Fancy URLs</label>
		    	<div class="custom-control custom-switch">            
		        	<input type="radio" name="fancy" id="fancy-enable" value="enable" checked='checked' class="custom-control-input" />
		        	<label class="custom-control-label" for="fancy-enable">Enable</label>
		        </div>
		        <div class="custom-control custom-switch">
		        	<input type="radio" name="fancy" id="fancy-disable" value="" class="custom-control-input" />
		        	<label class="custom-control-label" for="fancy-disable">Disable</label>
		        </div>
		        <small class="form-text text-muted">Enable fancy (pretty) URLs. Auto-detection failed, it depends on Javascript.</small>
        	</div>
        	<div class="col-12 col-sm-6">
		        <label for="ssl">Server SSL</label>
		        <div class="custom-control custom-switch">
		        	<input type="radio" name="ssl" id="ssl-always" value="always" {$ssl['always']} class="custom-control-input" />
		        	<label class="custom-control-label" for="ssl-always">Enable</label>
		        </div>
		        <div class="custom-control custom-switch">
		        	<input type="radio" name="ssl" id="ssl-never" value="never" {$ssl['never']} class="custom-control-input" />
		        	<label class="custom-control-label" for="ssl-never">Disable</label>
		        </div>
		        <small class="form-text text-muted">Enabling SSL (https://) requires extra webserver configuration and certificate generation not offered by this installation.</small>
		     </div>
        </div>

        <legend>Database settings</legend>
        <hr>
        <div class="form-group">
            <label for="dbtype">Type</label>
            {$dbRadios}
            <small class="form-text text-muted">Database type</small>
        </div>
        <div class="form-row">
        	<div class="col-12 col-sm-6">
				<div class="form-group">
				    <label for="host">Hostname</label>
				    <input type="text" id="host" name="host" class="form-control" value="{$post->value('host')}" />
				    <small class="form-text text-muted">Database hostname</small>
				</div>
				<div class="form-group">
				    <label for="database">Name</label>
				    <input type="text" id="database" name="database" class="form-control" value="{$post->value('database')}" />
				    <small class="form-text text-muted">Database name</small>
				</div>
		    </div>
		    <div class="col-12 col-sm-6">
		    	<div class="form-group">
				    <label for="dbusername">DB username</label>
				    <input type="text" id="dbusername" name="dbusername" class="form-control" value="{$post->value('dbusername')}" />
				    <small class="form-text text-muted">Database username</small>
				</div>
				<div class="form-group">
				    <label for="dbpassword">DB password</label>
				    <input type="password" id="dbpassword" name="dbpassword" class="form-control" value="{$post->value('dbpassword')}" />
				    <small class="form-text text-muted">Database password (optional)</small>
				</div>
		    </div> 
        </div>              

        <legend>Administrator settings</legend>
        <hr>
        <div class="form-row">
        	<div class="col-12 col-sm-6">
				<div class="form-group">
				    <label for="admin_nickname">Administrator nickname</label>
				    <input type="text" id="admin_nickname" name="admin_nickname" class="form-control" value="{$post->value('admin_nickname')}" />
				    <small class="form-text text-muted">Nickname for the initial user (administrator)</small>
				</div>
				<div class="form-group">
				    <label for="admin_password">Administrator password</label>
				    <input type="password" id="admin_password" name="admin_password" class="form-control" value="{$post->value('admin_password')}" />
				    <small class="form-text text-muted">Password for the initial user (administrator)</small>
				</div>
			</div>
			<div class="col-12 col-sm-6">
				<div class="form-group">
				    <label for="admin_password2">Confirm password</label>
				    <input type="password" id="admin_password2" name="admin_password2" class="form-control" value="{$post->value('admin_password2')}" />
				    <small class="form-text text-muted">Confirm your password</small>
				</div>
				<div class="form-group">
				    <label for="admin_email">Administrator e-mail</label>
				    <input id="admin_email" name="admin_email" class="form-control" value="{$post->value('admin_email')}" />
				    <small class="form-text text-muted">Optional email address for the initial user (administrator)</small>
				</div>
			</div>
		</div>

        <legend>Site profile</legend>
        <hr>
        <div class="form-group">
            <label for="site_profile">Type of site</label>
            <select id="site_profile" class="form-control" name="site_profile">
                <option value="community">Community</option>
                <option value="public">Public (open registration)</option>
                <option value="singleuser">Single User</option>
                <option value="private">Private (no federation)</option>
            </select>
            <small class="form-text text-muted">Initial access settings for your site</small>
        </div>

        <input type="submit" name="submit" class="btn btn-primary submit" value="Submit" />

    </form>

E_O_T;
    }

    /**
     * Handle a POST submission... if we have valid input, start the install!
     * Otherwise shows the form along with any error messages.
     */
    public function handlePost()
    {
        echo <<<STR
        <dl class="system_notice">
            <dt>Page notice</dt>
            <dd>
                <ul>
STR;
        $this->validated = $this->prepare();
        if ($this->validated) {
            $this->doInstall();
        }
        echo <<<STR
            </ul>
        </dd>
    </dl>
STR;
        if (!$this->validated) {
            $this->showForm();
        }
    }

    /**
     * Read and validate input data.
     * May output side effects.
     *
     * @return bool success
     */
    public function prepare(): bool
    {
        $post = new Posted();
        $this->host = $post->string('host');
        $this->dbtype = $post->string('dbtype');
        $this->database = $post->string('database');
        $this->username = $post->string('dbusername');
        $this->password = $post->string('dbpassword');
        $this->sitename = $post->string('sitename');
        $this->fancy = (bool)$post->string('fancy');

        $this->adminNick = strtolower($post->string('admin_nickname'));
        $this->adminPass = $post->string('admin_password');
        $adminPass2 = $post->string('admin_password2');
        $this->adminEmail = $post->string('admin_email');

        $this->siteProfile = $post->string('site_profile');

        $this->ssl = $post->string('ssl');

        $this->server = $_SERVER['HTTP_HOST'];
        $this->path = substr(dirname($_SERVER['PHP_SELF']), 1);

        $fail = false;
        if (!$this->validateDb()) {
            $fail = true;
        }

        if (!$this->validateAdmin()) {
            $fail = true;
        }

        if ($this->adminPass != $adminPass2) {
            $this->updateStatus("Administrator passwords do not match. Did you mistype?", true);
            $fail = true;
        }

        if (!in_array($this->ssl, ['never', 'always'])) {
            $this->updateStatus("Bad value for server SSL enabling.");
            $fail = true;
        }

        if (!$this->validateSiteProfile()) {
            $fail = true;
        }

        return !$fail;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
  	<head>
    	<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Install GNU social</title>
        <link rel="shortcut icon" href="favicon.ico"/>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    </head>
    <body id="install">
        <div class="d-flex flex-column flex-md-row align-items-center p-3 px-md-4 mb-3 bg-white border-bottom shadow-sm">
  			<img class="logo u-photo" src="theme/neo/logo.png" alt="GNU social"/>
		</div>
		<div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
  			<h1 class="display-4">Install GNU social</h1>
		</div>

        <div class="container">
		<?php
		$installer = new WebInstaller();
		$installer->main();
		?>
        </div>

        <div id="footer" class="py-5"></div>

    </body>
</html>