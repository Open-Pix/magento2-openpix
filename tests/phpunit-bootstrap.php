<?php
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Module class files (required later after Magento stubs are defined)

if (!function_exists('__')) {
    function __($text, ...$args)
    {
        if (empty($args)) {
            return $text;
        }
        return vsprintf($text, $args);
    }
}

if (!class_exists('\\Magento\\Framework\\App\\Helper\\AbstractHelper')) {
    eval(
        <<<'PHP'
namespace Magento\Framework\App\Helper {
    class AbstractHelper
    {
        public function __construct(...$args)
        {
            // no-op
        }
    }
}
PHP
    );
}

// Provide a basic Context class stub
if (!class_exists('\\Magento\\Framework\\App\\Helper\\Context')) {
    eval(
        <<<'PHP'
namespace Magento\Framework\App\Helper {
    class Context
    {
        public function __construct(...$args)
        {
        }
    }
}
PHP
    );
}

// Additional Magento stubs for controllers and models
if (!class_exists('\\Magento\\Framework\\App\\Action\\Action')) {
    eval(
        <<<'PHP'
namespace Magento\Framework\App\Action {
    class Action {
        public function __construct(...$args) {}
    }
}
PHP
    );
}

if (!class_exists('\\Magento\\Payment\\Model\\Method\\AbstractMethod')) {
    eval(
        <<<'PHP'
namespace Magento\Payment\Model\Method {
    class AbstractMethod {
        public function __construct(...$args) {}
    }
}
PHP
    );
}

if (!class_exists('\\Magento\\Payment\\Block\\Info')) {
    eval(
        <<<'PHP'
namespace Magento\Payment\Block {
    class Info {
        public function __construct(...$args) {}
        public function getInfo() { return $this; }
        public function getOrder() { return null; }
        public function getMethod() { return $this; }
        public function getTitle() { return 'Payment Method'; }
    }
}
PHP
    );
}

if (!class_exists('\\Magento\\Sales\\Model\\Order')) {
    eval(
        <<<'PHP'
namespace Magento\Sales\Model {
    class Order {
        public function __construct(...$args) {}
        public function getData($key = null) { return null; }
        public function getId() { return 1; }
        public function canInvoice() { return true; }
    }
}
PHP
    );
}

if (!class_exists('\\Magento\\Sales\\Model\\Order\\Invoice')) {
    eval(
        <<<'PHP'
namespace Magento\Sales\Model\Order {
    class Invoice {
        const CAPTURE_OFFLINE = 'offline';
        public function __construct(...$args) {}
    }
}
PHP
    );
}

// Add interface stubs for mocking
if (!interface_exists('\\Magento\\Framework\\App\\Action\\Context')) {
    eval(
        <<<'PHP'
namespace Magento\Framework\App\Action {
    interface Context {}
}
PHP
    );
}

if (!interface_exists('\\Magento\\Sales\\Api\\OrderRepositoryInterface')) {
    eval(
        <<<'PHP'
namespace Magento\Sales\Api {
    interface OrderRepositoryInterface {}
}
PHP
    );
}

if (!interface_exists('\\Magento\\Sales\\Api\\InvoiceRepositoryInterface')) {
    eval(
        <<<'PHP'
namespace Magento\Sales\Api {
    interface InvoiceRepositoryInterface {}
}
PHP
    );
}

if (
    !class_exists(
        '\\Magento\\Sales\\Model\\Order\\Email\\Sender\\InvoiceSender'
    )
) {
    eval(
        <<<'PHP'
namespace Magento\Sales\Model\Order\Email\Sender {
    class InvoiceSender {
        public function __construct(...$args) {}
    }
}
PHP
    );
}

if (
    !class_exists(
        '\\Magento\\Sales\\Model\\ResourceModel\\Order\\CollectionFactory'
    )
) {
    eval(
        <<<'PHP'
namespace Magento\Sales\Model\ResourceModel\Order {
    class CollectionFactory {
        public function __construct(...$args) {}
    }
}
PHP
    );
}

if (
    !class_exists('\\Magento\\Framework\\HTTP\\PhpEnvironment\\RemoteAddress')
) {
    eval(
        <<<'PHP'
namespace Magento\Framework\HTTP\PhpEnvironment {
    class RemoteAddress {
        public function __construct(...$args) {}
        public function getRemoteAddress() { return '127.0.0.1'; }
    }
}
PHP
    );
}

if (!class_exists('\\Magento\\Framework\\View\\Result\\PageFactory')) {
    eval(
        <<<'PHP'
namespace Magento\Framework\View\Result {
    class PageFactory {
        public function __construct(...$args) {}
    }
}
PHP
    );
}

if (!class_exists('\\Magento\\Framework\\Controller\\Result\\JsonFactory')) {
    eval(
        <<<'PHP'
namespace Magento\Framework\Controller\Result {
    class JsonFactory {
        public function __construct(...$args) {}
    }
}
PHP
    );
}

// Now require module files that depend on Magento base classes/stubs
foreach (
    [
        __DIR__ . '/../Pix/Helper/WebHookHandlers/ChargePaid.php',
        __DIR__ . '/../Pix/Helper/WebHookHandlers/ChargeExpired.php',
        __DIR__ . '/../Pix/Helper/WebHookHandlers/ConfigureHandler.php',
        __DIR__ . '/../Pix/Helper/WebHookHandlers/Order.php',
        __DIR__ . '/../Pix/Helper/Data.php',
    ]
    as $file
) {
    if (file_exists($file)) {
        require_once $file;
    }
}
