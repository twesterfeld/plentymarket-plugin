<?php // strict

namespace Ceevo\Providers;

use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Plugin\Templates\Twig;

use Plenty\Plugin\Events\Dispatcher;
use Plenty\Plugin\ServiceProvider;
use Plenty\Plugin\Log\Loggable;

use Ceevo\Services\PaymentService;
use Ceevo\Helper\PaymentHelper;
use Ceevo\Methods\CeevoPaymentMethodBase;
use Ceevo\Methods\CeevoPaymentMethodCV;
// use Ceevo\Methods\CeevoPaymentMethodDC;
// use Ceevo\Methods\CeevoPaymentMethodDD;
// use Ceevo\Methods\CeevoPaymentMethodOTSU;
// use Ceevo\Methods\CeevoPaymentMethodOTGP;
// use Ceevo\Methods\CeevoPaymentMethodOTIDL;
// use Ceevo\Methods\CeevoPaymentMethodPF;
// use Ceevo\Methods\CeevoPaymentMethodPP;

/**
 * Class CeevoServiceProvider
 * @package Ceevo\Providers
 */
class CeevoServiceProvider extends ServiceProvider
{
  use Loggable;
  var $availablePayments = array(
    'CV'    => 'CEEVO',
    // 'DC'    => 'Debit Card',
    // 'DD'    => 'Direct Debit',
    // 'OTSU'  => 'Sofort Banking',
    // 'OTGP'  => 'Giropay',
    // 'OTIDL' => 'iDeal',
    // 'PF'    => 'Post Finance',
    // 'PP'    => 'PayPal',
    //''    => '',
  );

  private $twig;
  
  /**
     * Register the route service provider
     */
    public function register()
    {
      $this->getApplication()->register(CeevoRouteServiceProvider::class);
      //$this->getApplication()->bind(RefundEventProcedure::class);
    }
    
    /**
     * Boot additional services
     *
     * @param Dispatcher               $eventDispatcher
     * @param PaymentHelper            $paymentHelper
     * @param PaymentService           $paymentService
     * @param BasketRepositoryContract $basket
     * @param PaymentMethodContainer   $payContainer
     * @param EventProceduresService   $eventProceduresService
     */
    public function boot(   Twig $twig, Dispatcher $eventDispatcher,
                            PaymentHelper $paymentHelper,
                            PaymentService $paymentService,
                            BasketRepositoryContract $basket,
                            PaymentMethodContainer $payContainer,
                            EventProceduresService $eventProceduresService)
    {
      
        // Create the ID of the payment method if it doesn't exist yet
        // $paymentHelper->createMopIfNotExists(PaymentHelper::PAYMENTKEY_CEEVO, PaymentHelper::PAYMENTKEY_CEEVO_NAME);
    
        $regName = 'ceevo::CEEVO'.PaymentHelper::PAYMENTKEY_CEEVO;
        $className = 'Ceevo\Methods\CeevoPaymentMethod'.PaymentHelper::PAYMENTKEY_CEEVO; 
        // Register the payment method in the payment method container
        $payContainer->register($regName, $className, [ AfterBasketChanged::class, AfterBasketItemAdd::class, AfterBasketCreate::class ]);
      
        $this->twig = $twig;
        // Listen for the event that gets the payment method content
        $eventDispatcher->listen(GetPaymentMethodContent::class,
            function(GetPaymentMethodContent $event) use( $paymentHelper,  $basket,  $paymentService)
            {
              if($paymentHelper->getPaymentKeyByMop($event->getMop())) {
                $basket = $basket->load();
                
                //$output = 'getMop: '.$event->getMop();
                $selectedPaymethod = '';
                $selectedMopID = '';
                
                  //$output.= 'getPaymentMethod: '.$paymentHelper->getPaymentMethod(PaymentHelper::PAYMENTKEY_CEEVO);
                  if ($paymentHelper->getPaymentMethod(PaymentHelper::PAYMENTKEY_CEEVO) == $event->getMop()){
                    $selectedPaymethod = PaymentHelper::PAYMENTKEY_CEEVO;
                    $selectedMopID = $paymentHelper->getPaymentMethod(PaymentHelper::PAYMENTKEY_CEEVO);
                  }
                
                //$output.= 'basket: '.$paymentService->getPaymentContent($basket, $selectedPaymethod);
                $this
                ->getLogger(__CLASS__ . '_' . __METHOD__)
                ->setReferenceType('this')
                ->setReferenceValue($this)
                ->info('Ceevo::Logger.infoCaption', [
                  'this' => $this,
                  'basket' => $basket, 
                ]);
                
                $content = $paymentService->getPaymentContent($this->twig, $basket, $selectedPaymethod, $selectedMopID);
                
                $event->setValue($content);
                $event->setType('htmlContent');
                
                $this
                  ->getLogger(__CLASS__ . '_' . __METHOD__)
                  //->setReferenceType('this')
                  //->setReferenceValue($this)
                  ->info('Ceevo::Logger.infoCaption', [
                    'this' => $this,
                    'basket' => $basket, 
                  ]);
              }  
            }
        );
        
        // Listen for the event that executes the payment
        $eventDispatcher->listen(ExecutePayment::class,
            function(ExecutePayment $event) use ( $paymentHelper, $paymentService)
            {
              if($paymentHelper->getPaymentKeyByMop($event->getMop())) {
                $selectedPaymethod = '';
                $selectedMopID = '';
                
                  if ($paymentHelper->getPaymentMethod(PaymentHelper::PAYMENTKEY_CEEVO) == $event->getMop()){
                    $selectedPaymethod = PaymentHelper::PAYMENTKEY_CEEVO;
                    $selectedMopID = $paymentHelper->getPaymentMethod(PaymentHelper::PAYMENTKEY_CEEVO);
                  }
                
                
                // Execute the payment
                $paymentRes = $paymentService->executePayment($event->getOrderId(), $selectedPaymethod, $selectedMopID);
                
                $this
                  ->getLogger(__CLASS__ . '_' . __METHOD__)
                  //->setReferenceType('this')
                  //->setReferenceValue($this)
                  ->info('Ceevo::Logger.infoCaption', [
                    'this' => $this,
                    'getOrderId' => $event->getOrderId(),
                    'selectedPaymethod' => $selectedPaymethod, 
                    'selectedMopID' => $selectedMopID,
                    'paymentRes' => $paymentRes, 
                  ]);
                
                if ($paymentRes['success']){
                  $event->setType('success');
                  $event->setValue('The Payment has been executed successfully!');
                } else {
                  $event->setType('errorCode');
                  $event->setValue('The Payment could not be executed!');
                }                
              }  
            }
        );
        
    }
    
}
