<?php


defined('MOODLE_INTERNAL') || die();
require_once('office365client.php');


/**
 * Moodle repository plugin for working with Microsoft OneDrive for Business.
 *
 * @package    repository_onedriveforbusiness
 * @copyright  2014 Werner Urech
 * @author     Werner Urech <info ät itsu dot ch>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_onedriveforbusiness extends repository {


    /**
     * The Office 365 client instance.
     *
     * @var oauth2_client_office365
     */
    protected $client;


    /**
     * Create the plugin instance.
     *
     * @param int $repositoryid -- repository instance id
     * @param int|stdClass $context -- a context id or context object
     * @param array $options -- repository options
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array())
    {
        parent::__construct($repositoryid, $context, $options, 1); // 1 = readonly

        // plugin options for webadmin
        $clientid = get_config('onedriveforbusiness', 'clientid');
        $clientsecret = get_config('onedriveforbusiness', 'clientsecret');
        $loginhint = get_config('onedriveforbusiness', 'loginhint');

        // oauth the moodle way... setup the repository auth callback url
        $returnurl = new moodle_url('/repository/repository_callback.php');
        $returnurl->param('callback', 'yes');
        $returnurl->param('repo_id', $this->id);
        $returnurl->param('sesskey', sesskey());

        $this->client = new oauth2_client_office365($clientid, $clientsecret, $loginhint, $returnurl);
    }


    /**
     * Check whether the user is logged in or not.
     *
     * @return bool -- true when logged in
     */
    public function check_login()
    {
        return $this->client->is_logged_in();
    }


    /**
     * Print or return the login form.
     *
     * @return void|array for ajax.
     */
    public function print_login()
    {
        $url = $this->client->get_login_url();

        if ($this->options['ajax']) {
            $popup = new stdClass();
            $popup->type = 'popup';
            $popup->url = $url->out(false);
            return array('login' => array($popup));
        } else {
            echo '<a target="_blank" href="'.$url->out(false).'">'.get_string('login', 'repository').'</a>';
        }
    }


    /**
     * Callback method during authentication.
     *
     * @return void
     */
    public function callback() {
        $code = optional_param('oauth2code', null, PARAM_RAW);
//        error_log('entering ' . __FUNCTION__ . " with authorization code: {$code}");
        if ($code) {
            // having the auth code, get the initial access and refresh tokens
            $this->client->upgrade_token($code);

            // now call the discovery api to get the sharepoint MyFiles service info
            $service = $this->client->discover_service();
            if (false === $service) {
                error_log('sharepoint service discovery failed');
                return;
            }

            // and update the access / refresh tokens for the discovered resource
            if (!$this->client->refresh_tokens()) {
                error_log('refreshing the tokens failed');
                return;
            }
        } else {
        	throw new Exception();
        }
    }


    /**
     * Given a path, and perhaps a search, get a list of files.
     *
     * See details on {@link http://docs.moodle.org/dev/Repository_plugins}
     *
     * @param string $path -- path of folder that shall be listed (utf8)
     * @param string $page -- the page number of file list
     * @return array -- list of files including meta information as specified by base repository class.
     */
    public function get_listing($path = '', $page = '')
    {
        $ret = array();
        $ret['dynload'] = true;
        $ret['nosearch'] = true;
        $ret['manage'] = $this->client->get_manage_url();

        $path = trim($path, '/');
        $fileslist = $this->client->get_file_list($path);

        // Filter list for accepted types. Hopefully this will be done by core some day.
        $fileslist = array_filter($fileslist, array($this, 'filter'));
        $ret['list'] = $fileslist;

        // generate the path breadcrumb, always start with the plugin name.
        $ret['path'] = array();
        $ret['path'][] = array('name'=> $this->name, 'path'=>'');

        // construct the breadcrumb... is for display only, so $path must remain utf8
        $trail = '';
        if ($path !== '') {
            $parts = explode('/', $path);
            if (count($parts) > 1) {
                foreach ($parts as $part) {
                    if (!empty($part)) {
                        $trail .= '/' . $part;
                        $ret['path'][] = array('name' => $part, 'path' => $trail);
                    }
                }
            } else {
                $ret['path'][] = array('name' => $path, 'path' => $path);
            }
        }

        return $ret;
    }


    /**
     * Download a file from repository and save it to a temporary path.
     *
     * @param string $url -- content url of the file
     * @param string $filename -- save as...
     * @return array -- path => internal location of the downloaded file, url => url to the source
     */
    public function get_file($url, $filename = '') {
        $path = $this->prepare_file($filename);
        return $this->client->download_file($url, $path);
    }

    /**
     * Return names of the options to display in the admin repository form
     *
     * @return array of option names
     */
    public static function get_type_option_names() {
        return array('clientid', 'clientsecret', 'loginhint', 'pluginname');
    }


    /**
     * Setup the webadmin repository form.
     *
     * @param moodleform $mform -- Moodle form (passed by reference)
     * @param string $classname -- repository class name
     */
    public static function type_config_form($mform, $classname = 'repository') {
        $a = new stdClass;
        $a->callbackurl = oauth2_client_office365::callback_url()->out(false);
        $mform->addElement('static', null, '', get_string('oauthinfo', 'repository_onedriveforbusiness', $a));

        parent::type_config_form($mform);
        $strrequired = get_string('required');
        $mform->addElement('text', 'clientid', get_string('clientid', 'repository_onedriveforbusiness'));
        $mform->addElement('text', 'clientsecret', get_string('clientsecret', 'repository_onedriveforbusiness'));
        $mform->addElement('text', 'loginhint', get_string('loginhint', 'repository_onedriveforbusiness'));
        $mform->addRule('clientid', $strrequired, 'required', null, 'client');
        $mform->addRule('clientsecret', $strrequired, 'required', null, 'client');
        $mform->setType('clientid', PARAM_RAW_TRIMMED);
        $mform->setType('clientsecret', PARAM_RAW_TRIMMED);
        $mform->setType('loginhint', PARAM_RAW_TRIMMED);
    }


    /**
     * Logout from repository instance and return login form.
     *
     * @return string -- link to external oauth login
     */
    public function logout() {
        $this->client->log_out();
        return $this->print_login();
    }


    /**
     * This repository doesn't support global search.
     *
     * @return bool -- true if supports global search
     */
    public function global_search() {
        return false;
    }


    /**
     * This repository supports any filetype.
     *
     * @return string -- supported file extensions, '*' means this any
     */
    public function supported_filetypes() {
        return '*';
    }


    /**
     * This repostiory only supports internal files.
     *
     * @return int -- bitmask of supported return types
     */
    public function supported_returntypes() {
        return FILE_INTERNAL;
    }


}
