<?php
namespace Akeneo\Connector\Controller\Adminhtml\Config\ExportPdf;

/**
 * Interceptor class for @see \Akeneo\Connector\Controller\Adminhtml\Config\ExportPdf
 */
class Interceptor extends \Akeneo\Connector\Controller\Adminhtml\Config\ExportPdf implements \Magento\Framework\Interception\InterceptorInterface
{
    use \Magento\Framework\Interception\Interceptor;

    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\App\Response\Http\FileFactory $fileFactory, \Akeneo\Connector\Model\Config\ConfigManagement $configManagement)
    {
        $this->___init();
        parent::__construct($context, $fileFactory, $configManagement);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'execute');
        return $pluginInfo ? $this->___callPlugins('execute', func_get_args(), $pluginInfo) : parent::execute();
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(\Magento\Framework\App\RequestInterface $request)
    {
        $pluginInfo = $this->pluginList->getNext($this->subjectType, 'dispatch');
        return $pluginInfo ? $this->___callPlugins('dispatch', func_get_args(), $pluginInfo) : parent::dispatch($request);
    }
}
