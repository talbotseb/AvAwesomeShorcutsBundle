<?php

namespace AppVentus\Awesome\ShortcutsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @author Leny BERNARD <leny@appventus.com>
 */
abstract class AwesomeController extends BaseController
{
    protected $tool;

    /**
     * Shortcut to dispatch event.
     *
     * @param  string $eventName
     * @param  Event  $event
     * @return void
     */
    public function dispatchEvent($eventName, Event $event = null)
    {
        $this->get('event_dispatcher')->dispatch($eventName, $event);
    }

    /**
     * Alert message to flashbag.
     *
     * @param string $content           Captain Obvious ? We have to setup a content
     * @param string $type              Success or Error ? Warning or Info ? You choose !
     * @param string $layout            How the noty should be rendered ? topRight ? top Left ? bottom ? Go to doc to know more
     * @param string $translationDomain The translation domain to use to translate strings
     */
    public function alert($content, $type = 'success', $layout = null, $translationDomain = null)
    {
        $this->get('av.shortcuts')->alert($content, $type, $layout, $translationDomain);
    }

    /**
     * congrat user through flashbag : all happened successfully
     *
     * @param string $content
     */
    public function congrat($content, $layout = null, $translationDomain = null)
    {
        $this->get('av.shortcuts')->congrat($content, $layout, $translationDomain);
    }

    /**
     * Warn user through flashbag: something requires attention
     *
     * @param string $content
     */
    public function warn($content, $layout = null, $translationDomain = null)
    {
        $this->get('av.shortcuts')->warn($content, $layout, $translationDomain);
    }

    /**
     * Inform user through flashbag: someething have to be said
     *
     * @param string $content
     */
    public function inform($content, $layout = null, $translationDomain = null)
    {
        $this->get('av.shortcuts')->inform($content, $layout, $translationDomain);
    }

    /**
     * Scold user through flashbag: someething went wrong
     *
     * @param string $content
     */
    public function scold($content, $layout = null, $translationDomain = null)
    {
        $this->get('av.shortcuts')->scold($content, $layout, $translationDomain);
    }

    /**
     * Add thing to flashbag.
     *
     * @param string $content
     * @param string $type
     * @param string $layout
     */
    public function setFlash($name, $value)
    {
        $this->get('av.shortcuts')->setFlash($name, $value);
    }

    public function isGranted($attributes, $object = null)
    {
        return $this->get('security.context')->isGranted($attributes, $object);
    }

    public function getSession($name, $default = null)
    {
        return $this->get('session')->get($name, $default);
    }

    public function setSession($name, $value)
    {
        $this->get('session')->set($name, $value);
    }

    /**
     * Shortcut to persist and flush an entity.
     *
     * @param Object $entity
     */
    public function persistAndFlush($entity)
    {
        $em = $this->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * Shortcut to entity manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    public function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Shortcut to remove and flush an entity.
     *
     * @param object $entity
     */
    public function removeAndFlush($entity)
    {
        $em = $this->getEntityManager();
        $em->remove($entity);
        $em->flush();
    }

    public function createAndQueueMail($subject, $from, $to, $body, $contentType = 'text/html', $replyTo = null, $attachments = array())
    {
        $controller = $this->getRequest()->attributes->get('_controller');

        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, $contentType)
            ;
        foreach ($attachments as $attachment) {
            if ($attachment instanceof UploadedFile) {
                $message
                   ->attach(\Swift_Attachment::newInstance($attachment, $attachment->getClientOriginalName(), $attachment->getMimeType()));
            }

        }
        if ($replyTo != null) {
            $message->setReplyTo($replyTo);
        }

        $this->get('white_october.swiftmailer_db.spool')->queueMessage($message, $controller);
    }

    public function createAndSendMail($subject, $from, $to, $body, $contentType = 'text/html', $replyTo = null, $attachments = array(), $mailer = 'mailer')
    {
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($body, $contentType)
            ;

        foreach ($attachments as $attachment) {
            if ($attachment instanceof UploadedFile) {
                $message
                  ->attach(\Swift_Attachment::newInstance($attachment, $attachment->getClientOriginalName(), $attachment->getMimeType()));
            }

        }
        if ($replyTo != null) {
            $message->setReplyTo($replyTo);
        }
        $this->get($mailer)->send($message);
    }

    public function isGrantedOr403($attributes, $object = null, $message = null)
    {
        if ($this->get('security.context')->isGranted($attributes, $object)) {
            return;
        }

        throw $this->createAccessDeniedException($message);
    }

    public function createAccessDeniedException($message = 'Access Denied', \Exception $previous = null)
    {
        return new AccessDeniedException($message, $previous);
    }

    public function getCurrentUserOr403()
    {
        $user = $this->getUser();

        if (null === $user) {
            throw $this->createAccessDeniedException('This user does not have access to this section.');
        }

        return $user;
    }

    /**
     * Return logged user
     *
     * @return User
     */
    public function getUser()
    {
        if (null === $token = $this->container->get('security.context')->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    public function redirectReferer()
    {
        $url = $this->container->get('request')->headers->get('referer');
        if (empty($url)) {
            $url = $this->container->get('router')->generate('home');
        }

        return new RedirectResponse($url);
    }

    public function isReferer($url)
    {
        return $url === $this->container->get('request')->headers->get('referer');
    }

    public function findEntityOr404($entity, $criteria)
    {
        if (method_exists($this, 'get'.$entity.'Repository')) {
            $obj = $this->{'get'.$entity.'Repository'}()->findOneBy($criteria);
        } else {
            throw new \BadMethodCallException(
                'Undefined method "get' . $entity . 'Repository". Please ' .
                'make sure both method and entity exist.'
            );
        }

        if (null === $obj) {
            throw $this->createNotFoundException(sprintf(
                '%s with parameter(s) %s couldn\'t be found',
                $entity,
                http_build_query($criteria)
            ));
        }

        return $obj;
    }

    public function preExecute()
    {
        $this->tool = $this->get('av.tool');
        $this->urlizer = $this->get('gedmo.urlizer');
    }

    /**
     * Get browser
     *
     * @return string
     */
    public function getBrowser()
    {

        $u_agent  = $_SERVER['HTTP_USER_AGENT'];
        $bname    = $u_agent;
        $platform = 'Unknown';
        $version  = '';

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'linux';
        } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'mac';
        } elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'windows';
        }

        // Next get the name of the useragent yes seperately and for good reason
        if (preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        } elseif (preg_match('/Firefox/i',$u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        } elseif (preg_match('/Chrome/i',$u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        } elseif (preg_match('/Safari/i',$u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        } elseif (preg_match('/Opera/i',$u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        } elseif (preg_match('/Netscape/i',$u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) .
        ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)) {
                $version= $matches['version'][0];
            } else {
                $version= $matches['version'][1];
            }
        } else {
            $version= $matches['version'][0];
        }

        // check if we have a number
        if ($version == null || $version == '') {
            $version = '?';
        }

        return $bname . ' ' . $version . ' ' . $platform;
    }

}
