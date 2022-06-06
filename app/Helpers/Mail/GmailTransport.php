<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2022. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Helpers\Mail;

use App\Utils\TempFile;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Dacastro4\LaravelGmail\Services\Message\Mail;
use Illuminate\Mail\Transport\Transport;
use Swift_Mime_SimpleMessage;

/**
 * GmailTransport.
 */
class GmailTransport extends Transport
{
    /**
     * The Gmail instance.
     *
     * @var Mail
     */
    protected $gmail;

    /**
     * Create a new Gmail transport instance.
     *
     * @param Mail $gmail
     * @param string $token
     */
    public function __construct(Mail $gmail)
    {
        $this->gmail = $gmail;
    }

    public function send(Swift_Mime_SimpleMessage $message, &$failedRecipients = null)
    {
        /* For some reason the Injected Mail class carries cached tokens, so we need to reinit the Mail class*/
        $this->gmail = null;
        $this->gmail = new Mail;

        /*We should nest the token in the message and then discard it as needed*/
        $token = $message->getHeaders()->get('GmailToken')->getValue();
        
        $message->getHeaders()->remove('GmailToken');

        $this->beforeSendPerformed($message);

        $this->gmail->using($token);
        $this->gmail->to($message->getTo());
        $this->gmail->from($message->getFrom());
        $this->gmail->subject($message->getSubject());
        $this->gmail->message($message->getBody());

        $this->gmail->cc($message->getCc());

        if(is_array($message->getBcc()))
            $this->gmail->bcc(array_keys($message->getBcc()));

        foreach ($message->getChildren() as $child) 
        {

            if($child->getContentType() != 'text/plain')
            {

                $this->gmail->attach(TempFile::filePath($child->getBody(), $child->getHeaders()->get('Content-Type')->getParameter('name') ));
            
            }

        } 

        /**
         * Google is very strict with their
         * sending limits, if we hit 429s, sleep and
         * retry again later.
         */
        try{

            $this->gmail->send();

        }
        catch(\Google\Service\Exception $e)
        {
            nlog("gmail exception");
            nlog($e->getErrors());

            sleep(5);
            $this->gmail->send();
        }

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }
}
