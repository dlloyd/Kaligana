<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\NewsletterEmails;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return $this->render('default/index.html.twig');
    }


    /**
     * @Route("/about-us", name="about_us")
     */
    public function aboutUsAction()
    {
        return $this->render('default/about.html.twig');
    }

    /**
     * @Route("/newsletter/", name="newsletter")
     * @Method("GET")
     */
    public function newsletterAction(){

        $form = $this->newsletterForm();
        return $this->render('default/newsletter.html.twig',array('form'=>$form->createView(),));
    }


    /**
     * @Route("/newsletter/save/", name="save_email_in_newsletter")
     * @Method("POST")
     */
    public function saveToNewsletterAction(Request $req){
        
        if (!$req->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => 'Uniquement requête Ajax'), 400);
        }

        $form = $this->newsletterForm();
        
        $form->HandleRequest($req);
        if($form->isSubmitted() && $form->isValid()){
            $em = $this->getDoctrine()->getManager();
            $formData = $form->getData();
            $emailExist = $em->getRepository('AppBundle:NewsletterEmails')->findBy(['email'=>$formData['email'],]);

            if($emailExist){
                return new JsonResponse(array('message' => 'Vous êtes déjà enregistré !'), 400);
            }
            $newsletterEmail = new NewsletterEmails();
            
            $newsletterEmail->setEMail($formData['email']);
            $em->persist($newsletterEmail);
            $em->flush();
            return new JsonResponse(array('message' => 'Success!'), 200);    
        }

        return new JsonResponse(array('error' => 'Form error'), 400);
    }


    public function newsletterForm(){
        $data = array();
        return $this->createFormBuilder($data)
            ->add('email', EmailType::class)
            ->add('save', SubmitType::class, array('label' => 'Envoyer'))
            ->getForm();
    }
}
