<?php
class WTC_AIS_Model_Observer
{

	public function autoInvoice($observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orders = Mage::getModel('sales/order_invoice')->getCollection()
                        ->addAttributeToFilter('order_id', array('eq'=>$order->getId()));
        $orders->getSelect()->limit(1);
        if ((int)$orders->count() !== 0) {
            return $this;
        }
        if ($order->getState() == Mage_Sales_Model_Order::STATE_NEW) {
            try {
                if(!$order->canInvoice()) {
                    $order->addStatusHistoryComment('WTC_AIS: Order cannot be invoiced.', false);
                    $order->save();
                }
                //START Handle Invoice
                $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
                $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                $invoice->getOrder()->setCustomerNoteNotify(false);
                $invoice->getOrder()->setIsInProcess(true);
				$invoice->sendEmail(true, '');
                $order->addStatusHistoryComment('Automatically INVOICED by WTC_AIS.', false);
                $transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
                $transactionSave->save();
                //END Handle Invoice
            } catch (Exception $e) {
                $order->addStatusHistoryComment('WTC_AIS: Exception occurred during automaticallyInvoiceShipCompleteOrder action. Exception message: '.$e->getMessage(), false);
                $order->save();
            }
        }
		return $this;
    }
		
}
