<?php
namespace Skeletor\Tenant\Filter;

use Skeletor\Core\Filter\Str;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Security\Csrf;
use Skeletor\Tenant\Validator\Tenant as TenantValidator;
use Skeletor\Core\Validator\ValidatorException;

class Tenant implements FilterInterface
{
    public function __construct(private TenantValidator $validator)
    {
    }

    public function getErrors()
    {
        return $this->validator->getMessages();
    }

    public function filter(array $postData): array
    {
        $alnum = static fn ($v) => Str::alnum((string) $v, true);
        $settings = [
            'useSef' => (isset($postData['settings']['useSef']) && $postData['settings']['useSef'] === 'on') ? 1 : 0,
            'useForeignInvoice' => (isset($postData['settings']['useForeignInvoice']) && $postData['settings']['useForeignInvoice'] === 'on') ? 1 : 0,
            'useSefToManageInput' => (isset($postData['settings']['useSefToManageInput']) && $postData['settings']['useSefToManageInput'] === 'on') ? 1 : 0,
            'sefApiKey' => $postData['settings']['sefApiKey'],
            'invoiceCodePattern' => $postData['settings']['invoiceCodePattern'],
            'foreignInvoiceCodePattern' => $postData['settings']['foreignInvoiceCodePattern'],
            'filenamePattern' => $postData['settings']['filenamePattern'],
            'inVatSystem' => (isset($postData['settings']['inVatSystem']) && $postData['settings']['inVatSystem'] === 'on') ? 1 : 0,
            'customStartingNumber' => (int) ($postData['settings']['customStartingNumber']),
        ];
        if((isset($postData['vatExemption']) && $postData['vatExemption'] !== '')) {
            $settings['vatExemption'] = $postData['vatExemption'];
        }

        $data = [
            'id' => $postData['id'],
            'name' => $postData['name'],
            'description' => $postData['description'],
            'settings' => json_encode($settings),
            'pib' => $postData['pib'],
            'mb' => $postData['mb'],
            'isActive' => 1,
            'bank' => $postData['bank'],
            'accountNumber' => $postData['accountNumber'],
//            'logo' => $postData['logo'],
            'logo' => '',
            'website' => $postData['website'],
            'email' => $postData['email'],
            'address' => $postData['address'],
            Csrf::TOKEN_NAME => $postData[Csrf::TOKEN_NAME],
//            'invoiceInstruction' => isset($postData['invoiceInstruction']) ? $postData['invoiceInstruction'] : [],
        ];

//        $data['client'][Csrf::TOKEN_NAME] = $postData[Csrf::TOKEN_NAME];
        $valid = true;
//        if (!$this->clientValidator->isValid($data['client'])) {
//            $valid = false;
//        }
        if (!$this->validator->isValid($data)) {
            $valid = false;
        }
        if (!$valid) {
            throw new ValidatorException();
        }
        unset($data[Csrf::TOKEN_NAME]);

        return $data;
    }

}