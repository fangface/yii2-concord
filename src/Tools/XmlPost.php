<?

namespace Concord\Tools;

use yii\base\Model;
use Concord\Tools\Xml;

class XmlPost extends Model {

    /**
     * @var Xml|false The request object
     */
    public $request = false;

    /**
     * @var string The URL to which the request should be sent
     */
    public $url = '';

    /**
     * @var integer The time before a request should be considered as failed
     */
    public $timeout = 60;


    /**
     * @var Xml|false The response object
     */
    public $response = false;

    /**
     * @var string The request document
     */
    public $remote_request = '';

    /**
     * @var string The response document
     */
    public $remote_response = '';

    /**
     * @var string The response status code
     */
    public $remote_status = '';

    /**
     * @var integer The response status description
     */
    public $remote_reason = '';

    /**
     * @var boolean Should SSL certificate peer be verified
     */
    public $verify_peer = true;

    /**
     * @var boolean Should SSL certificate host be verified
     */
    public $verify_host = true;


    /**
     * Call by construct
     */
	public function init() {
        $this->reset();
	}


    /**
     * Reset response variables
     */
	public function reset() {
        if (!$this->response instanceof Xml) {
	       $this->response = New Xml();
        } else {
            $this->response->resetArray();
        }
		$this->remote_request = '';
		$this->remote_response = '';
		$this->remote_status = '';
		$this->remote_reason = '';

	}


	/**
	 * Send request to remote server and load up the response
	 *
	 * @param Xml $request
	 * @param string $url
	 * @param integer $timeout
	 * @return boolean Success
	 */
	public function process($request = false, $url = false, $timeout = false, $response = false) {

	    $result = false;

		if ($request instanceof Xml) {
		    $this->request = $request;
		}

		if ($url) {
		    $this->url = $url;
		}

		if ($timeout) {
		    $this->timeout = $timeout;
		}

		if ($response instanceof Xml) {
		    $this->response = $response;
		}

		$this->reset();

		if (!$this->request) {
            $this->remote_status = 'X502';
            $this->remote_reason = 'No request set';
		} elseif (!$this->request instanceof Xml) {
            $this->remote_status = 'X502';
            $this->remote_reason = 'Invalid request set';
		} elseif (!$this->url) {
		    $this->remote_status = 'X502';
		    $this->remote_reason = 'No URL set';
		} elseif (!$this->response instanceof Xml) {
            $this->remote_status = 'X502';
            $this->remote_reason = 'Invalid response set';
		} else {

            $POSTCh = curl_init();

            curl_setopt($POSTCh, CURLOPT_URL, $this->url);
    		curl_setopt($POSTCh, CURLOPT_POST, 1 );

    		$this->remote_request = '<' . '?xml version="1.0" encoding="UTF-8"' . '?' .'>' . "\n";
    		$this->remote_request .= $this->request->getDocument();

    		curl_setopt($POSTCh, CURLOPT_POSTFIELDS, $this->remote_request);
    		curl_setopt($POSTCh, CURLOPT_HTTPHEADER, array('Expect: ') );
    		curl_setopt($POSTCh, CURLOPT_RETURNTRANSFER, 1);
    		curl_setopt($POSTCh, CURLOPT_TIMEOUT, $this->timeout);

            if (!$this->verify_host) {
                curl_setopt($POSTCh, CURLOPT_SSL_VERIFYHOST, 0);
            }

            if (!$this->verify_peer) {
                curl_setopt($POSTCh, CURLOPT_SSL_VERIFYPEER, false);
            }

    		$POSTResponse = curl_exec($POSTCh);
    		$POSTErrNo = curl_errno($POSTCh);
    		$POSTErrStr = curl_error($POSTCh);
    		$POSTGetInfo = curl_getinfo($POSTCh);
    		curl_close($POSTCh);

    		$this->remote_response = $POSTResponse;

    		if ($POSTErrNo > 0) {
    			$this->remote_status = 'X502';
    			$this->remote_reason = $POSTErrNo . ' - ' . $POSTErrStr;
    		} elseif ($POSTGetInfo["http_code"] != 200) {
    			$this->remote_status = 'X' . $POSTGetInfo["http_code"];
    			$this->remote_reason = 'HTTP:'. $POSTGetInfo["http_code"];
    		} else {
    		    if ($this->response->getRootElement() == '' || strpos($POSTResponse,'<' . $this->response->getRootElement() . '>') !== false) {
    		        if ($this->response->readDocumentFromString($POSTResponse)) {
    				    $this->remote_status = 'P200';
    				    $this->remote_reason = 'HTTP:'. $POSTGetInfo["http_code"];
    					$result = true;
    				} else {
    					$this->remote_status = 'X502';
    					$this->remote_reason = 'Invalid response - failed to read document';
    				}

    			} else {
    				$this->remote_status = 'X502';
    				$this->remote_reason = 'Invalid response - document not as expected';
    			}
    		}
        }

        return $result;
	}

}
