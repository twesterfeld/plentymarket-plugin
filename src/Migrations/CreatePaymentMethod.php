<?php
namespace Ceevo\Migrations;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Ceevo\Helper\PaymentHelper;

class CreatePaymentMethod
{
    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepositoryContract;
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
    /**
     * CreatePaymentMethod constructor.
     *
     * @param PaymentMethodRepositoryContract $paymentMethodRepositoryContract
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(    PaymentMethodRepositoryContract $paymentMethodRepositoryContract,
                                    PaymentHelper $paymentHelper)
    {
        $this->paymentMethodRepositoryContract = $paymentMethodRepositoryContract;
        $this->paymentHelper = $paymentHelper;
    }

    public function run()
    {
        if($this->paymentHelper->getPaymentMethod(PaymentHelper::PAYMENTKEY_CEEVO) == 'no_paymentmethod_found')
        {
            $paymentMethodData = array( 'pluginKey'   => 'ceevo',
                                        'paymentKey'  => 'CEEVO'.PaymentHelper::PAYMENTKEY_CEEVO,
                                        'name'        => 'Ceevo '.PaymentHelper::PAYMENTKEY_CEEVO_NAME);                                     
            $this->paymentMethodRepositoryContract->createPaymentMethod($paymentMethodData);
        }        
    }
}
?>