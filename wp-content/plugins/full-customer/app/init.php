<?php

use Full\Customer\License;

defined('ABSPATH') || exit;

require_once FULL_CUSTOMER_APP . '/controller/inc/License.php';

require_once FULL_CUSTOMER_APP . '/api/Controller.php';

require_once FULL_CUSTOMER_APP . '/api/Connection.php';
require_once FULL_CUSTOMER_APP . '/api/Copy.php';
require_once FULL_CUSTOMER_APP . '/api/Env.php';
require_once FULL_CUSTOMER_APP . '/api/Health.php';
require_once FULL_CUSTOMER_APP . '/api/PluginInstallation.php';
require_once FULL_CUSTOMER_APP . '/api/ElementorTemplates.php';
require_once FULL_CUSTOMER_APP . '/api/Widgets.php';

require_once FULL_CUSTOMER_APP . '/controller/inc/Health.php';
require_once FULL_CUSTOMER_APP . '/controller/inc/RemoteLogin.php';

if (License::isActive()) :
  require_once FULL_CUSTOMER_APP . '/api/PluginUpdate.php';
  require_once FULL_CUSTOMER_APP . '/api/Whitelabel.php';
endif;

require_once FULL_CUSTOMER_APP . '/controller/hooks.php';
require_once FULL_CUSTOMER_APP . '/controller/actions.php';
require_once FULL_CUSTOMER_APP . '/controller/filters.php';
require_once FULL_CUSTOMER_APP . '/controller/helpers.php';

require_once FULL_CUSTOMER_APP . '/controller/FullCustomerHttp.php';
require_once FULL_CUSTOMER_APP . '/controller/FullCustomerConnection.php';
require_once FULL_CUSTOMER_APP . '/controller/FullCustomerUpdate.php';
require_once FULL_CUSTOMER_APP . '/controller/FullCustomerStaffRepository.php';
