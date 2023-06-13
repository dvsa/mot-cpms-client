# CPMS Rest Client

Introduction
------------
This a module designed to make restful API calls to CPMS backend API. It handles 

* The generation of the required access token based on the scope 
* Logging when a logger alias is provided
* Caching of access tokens when caching is setup and enabled.
 

Installation
-------------
The recommended way to install is through [Composer][composer].
```
composer require dvsa/mot-cpms-client
```
 
### Configuration
         
3. Modify the configuration in the client-config.global.php file as follows:

         ```
             return array(
                 'cpms_api'                => array(
                     'version'           => 1,  
                     'logger_alias'      => '',  
                     'identity_provider' => '',
                     'enable_cache'      => true, //Enable of caching of access tokens for reuse
                     'cache_storage'     => 'filesystem',
                     'rest_client'   => array(
                         'options' => array(
                             'domain'             => ''
                         ),
                     ),
                 ),
             );
   
   * ```version```          : This is the version of CPMS API to target, currently on version 1
   * ```logger_alias```     : Zend Service Manager alias for retrieving a Laminas\Log instance
   * ```identity_provider``` : This is the service manager alias that should return an object which implements ```CpmsClient\Authenticate\IdentityProviderInterface```. 
   This is mandatory. The following information is retrieved from the class
   
     * ```client_id```     : The ID issues by CPMS that uniquely identifies the Scheme e.g. OLCS
     * ```client_secret``` : Hash value issued by CPMS
     * ```user_id```       : This is the OpenAM UUID of the logged in user. It is the responsiblity of the scheme to ensure that the data
      provided is valid as it would be logged against any action performed in CPMS for audit purposes.
      * ```customer_reference``` : This is the unique identifier for the customer on who is making the payment. In MoT this is the AE and in 
      OLCS it would be the operator etc.
   * ```enable_cache``` : Enable caching of access tokens for reuse.
   * ```cache_storage```    : Zend cache storage alias which would be load using Zend's StorageCacheAbstractServiceFactory. The default is ```filesystem```
   * ```domain```           : The API domain to which all API calls will be sent.
   
### Controller Plugin
The ```cpms-client``` module ships with a controller plugin which simplifies usage of the Rest Client. In your controller:
 
        $client         = $this->getCpmsRestClient(); 
   
Usage
-----
Below a typical usage of the ```cpms-client``` module. 

* Requesting access token to make a card payment ```POST```. Note that this is just an example for usage. By default, the module does this for you
behind the scenes.
        
        $requiredScope  = 'CARD';
        $endPoint       = '/api/token';
        $client         = $this->getCpmsRestClient();
        $params         = [
            'sales_reference' => 'your invoice number ',
            ......
        ];
        
        $response       = $client->post($endPoint, $requiredScope, $params);

* Retrieving payment details and specifying required fields, page, depth etc ```GET```. The client would generate the required 
access token for you, so you do not need to manually generate the access token. 

            $requiredScope = 'QUERY_TXN';
            $endPoint      = '/api/payment';
            
             $params = array(
                  'page'   => 1,
                  'sort'   => 'id:desc',
                  'params' => array(
                      'depth'           => -2,
                      'required_fields' => array(
                          'payment'       => array(
                              'created_on',
                              'receipt_reference',
                              'scope',
                              'total_amount',
                              'payment_details',
                              'payment_status',
                              'customer_reference',
                              'created_by'
                          ),
                          'paymentDetail' => array(
                              'product_reference',
                              'sales_reference',
                              'payment_reference',
                              'amount'
                          ),
                          'scope'         => array(
                              'code',
                              'name'
                          ),
                          'paymentStatus' => array(
                              'code',
                              'name',
                          )
                      )
                  ),
              );
              
              $payments = $this->getCpmsRestClient()->get($endPoint, $requiredScope, $params);
             
   * Updating the status of a mandate ```PUT```
   
            $requiredScope = 'DIRECT_DEBIT';
            $endPoint      = '/api/mandate/<mandate_reference>/status';
            $params        = [
                   'status' => '<status code>'
            ];
            $response      = $this->getCpmsRestClient()->get($endPoint, $requiredScope, $params);
            
### Specifying Required Fields
  When querying CPMS for data e.g. payment details, it is recommended that you specify the fields you want returned in the request. If not specified, 
  CMS would return the defaults fields which may change. The fields available are the column names as specified in the 
  [database scheme] (https://wiki.i-env.net/display/EA/CPMS+Interface+Service+Definition#CPMSInterfaceServiceDefinition-DataModel). 
#### Required field from main table e.g. Payment
        $requiredFields = [
              'created_on',
              'receipt_reference',
              'scope',
              'total_amount',
              'payment_details',
              'payment_status',
              'customer_reference',
              'created_by'
        ]
#### Required fields from join tables e.g PaymentDetails. Payment has a (one-to-many) relationship with PaymentDetails.
Note that the field names are underscore separated while the entity names are camel cases.

            $requiredFields = [
                'payment'       => [
                      'created_on',
                      'receipt_reference',
                      'scope',
                      'total_amount',
                      'payment_details',
                      'payment_status',
                      'customer_reference',
                      'created_by'
                  ],
                  'paymentDetail' => [
                      'product_reference',
                      'sales_reference',
                      'payment_reference',
                      'amount'
                  ],
              ]

Contributing
------------
Please refer to our [Contribution Guide](/CONTRIBUTING.md).
