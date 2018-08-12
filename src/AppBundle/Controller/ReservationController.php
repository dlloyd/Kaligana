<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Entity\Reservation;
use AppBundle\Entity\Invoice;


class ReservationController extends Controller
{
    /**
     * @Route("/new/reservation/{productId}",requirements={"productId" = "\d+"}, name="new_reservation")
     * @Method("GET")
     */
    public function newAction(Request $req,$productId){

        $em = $this->getDoctrine()->getManager();
        $prod = $em->getRepository('AppBundle:Product')->find($productId);

        if(!$prod || $prod->getIsHidden()){
            throw $this->createNotFoundException('Produit supprimé ou inexistant');
        }
        else{
            $form = $this->reservationForm($prod);
            
        return $this->render('reservation/create.html.twig',array('form' => $form->createView(),'id'=>$productId));
        }
   
    }

	/**
     * @Route("/create/reservation/{productId}",requirements={"productId" = "\d+"}, name="create_reservation")
     * @Method("POST")
     */
    public function createAction(Request $req,$productId){
        if (!$req->isXmlHttpRequest()) {
            return new JsonResponse(array('message' => 'Uniquement requête Ajax'), 400);
        }

    	$em = $this->getDoctrine()->getManager();
        
    	$prod = $em->getRepository('AppBundle:Product')->find($productId);

        if(!$prod || $prod->getIsHidden()){
            throw $this->createNotFoundException('Produit supprimé ou inexistant');
        }
        else{
            $form = $this->reservationForm($prod);
        }

    	$form->HandleRequest($req);
        if($form->isSubmitted() && $form->isValid()){
    		//add reservation to session
    		$session = $req->getSession();
            
    		if($session->has('reservations')){
                $resas = $session->get('reservations');
                $reservations = array();
                /* Vérifie si une resa du meme service existe, si c'est le cas il la supprime */
                foreach ($resas as $resa) {
                    $resa_prod = $em->getRepository('AppBundle:Product')->find($resa['productId']);
                    if($resa_prod->getProductType()->getId() != $prod->getProductType()->getId()){
                        array_push($reservations, $resa) ;
                    }
                }
                
                array_push($reservations, $form->getData()) ;
    			$session->set('reservations',$reservations);
    		}
            else{
                $reservations = array();
                array_push($reservations, $form->getData());
                $session->set('reservations',$reservations);
            }
    		
            /* Permet de savoir si un service a déja une réservation */
            if($prod->getProductType()->getCode()=="LOD"){
                $session->set('resa_lodgment',true);
            }
            elseif ($prod->getProductType()->getCode()=="CAR") {
                $session->set('resa_car',true);
            }
            /* fin */

    		return new JsonResponse(array('message' => 'Success!'), 200);    
        }

        return new JsonResponse(array('error' => 'Erreur formulaire'), 400);
    }



    /**
     * @Route("/reservation/validation/", name="validate_payment_reservation")
     */
    public function paymentAction(Request $req){
        $em = $this->getDoctrine()->getManager();
        $resaAmount = array();
        
        $session = $req->getSession();
        
        if(!$session->has('reservations')){
            return $this->redirectToRoute('homepage');
        }
        else{
          $resasSession = $session->get('reservations');
          $amount = 0;
          foreach ($resasSession as $resa) {
              $prod = $em->getRepository('AppBundle:Product')->find($resa['productId']);
              $resaAmount[$prod->getId()] =  $resa['quantity']*$prod->getPriceUnit();
              $resaProduct[$prod->getId()] = $prod;
              $amount += $resaAmount[$prod->getId()];
          }
            
          $form = $this->paymentForm();
         
          if ($req->isMethod('POST')) {
            $form->handleRequest($req);
         
            if ($form->isSubmitted() && $form->isValid() ) {
              try{
                \Stripe\Stripe::setApiKey($this->getParameter('stripe_secret_key'));
                $firstname = $form->get('firstname')->getData();
                $lastname = $form->get('lastname')->getData();
                $email = $form->get('email')->getData();
                $charge = \Stripe\Charge::create(array(
                  'amount'   => $amount*100,
                  'currency' => 'eur',
                  'source' => $form->get('token')->getData(),
                  'receipt_email' => $email
                ));

                $invoice = $this->createInvoiceReservations($resasSession,$firstname,$lastname,$email);

                //Envoyer email avec infos

                // detruit les données de réservation en session
                $session->remove('reservations');
                $session->remove('resa_lodgment');
                $session->remove('resa_car');
                return $this->render('payment/invoice.html.twig',array(
                    'resaAmount' => $resaAmount,
                    'amount'=>$amount,
                    'invoice'=>$invoice,
                    ));

              }
              catch(\Stripe\Error\Base $e){
                $this->addFlash('warning', $e->getMessage());
              }
            }
          }
         
        return $this->render('payment/validation.html.twig', [
          'form' => $form->createView(),
          'stripe_public_key' => $this->getParameter('stripe_public_key'),
          'amount' => $amount,
          'resaAmount' => $resaAmount,
          'resaProduct' => $resaProduct,
          'reservations'=>$resasSession,
        ]);
        }
    }

    /**
     * @Route("/admin/show/reservations/", name="show_all_reservations")
     */
    public function showAllAction(Request $req){

    	$em = $this->getDoctrine()->getManager();
    	$reservations = $em->getRepository('AppBundle:Reservation')->findAll();
    	return $this->render('reservation/reservations.html.twig', array('reservations' => $reservations ));
    }

    /**
     * @Route("/admin/show/inv/{code}", name="show_invoice_by_code")
     */
    public function showInvoiceByCodeAction($code){
    	$em = $this->getDoctrine()->getManager();
    	$res = $em->getRepository('AppBundle:Invoice')->findBy(['code' => $code]);
    	if($res){
    		return $this->render('reservation/invoice.html.twig', array('invoice' => $res )); 
    	}

    	$request->getSession()->getFlashBag()->add('error','Code facture inexistant');
        return $this->redirectToRoute('show_all_reservations');
    }

    /**
     * @Route("/resa/sess/remove/}", name="remove_reservation_session")
     */
    public function removeSessionReservationsAction(Request $req){
        $session = $req->getSession();
        if($session->has('reservations')){
            $session->remove('reservations');
        }
        return $this->redirectToRoute('homepage');

    }

    /**
     * @Route("/resa/sess/remove/prod/{id}}",requirements={"id" = "\d+"}, name="remove_reservation_product")
     */
    public function removeSessionReservationProductAction(Request $req,$id){
        $session = $req->getSession();
        if($session->has('reservations')){
            $newReservations = array();
            foreach ($session->get('reservations') as $resa) {
                if($resa['productId'] != $id){
                    array_push($newReservations,$resa);
                }
            }
            $session->set('reservations',$newReservations);
            if(empty($newReservations)){
                $session->remove('reservations');
            }

            
        }
        return $this->redirectToRoute('validate_payment_reservation');

    }




    public function generateCode($reservation){  //génère invoice code 
    	return null;
    }

    public function activityForm($resa,$productId){
        return $this->createFormBuilder($resa)
            ->add('quantity', IntegerType::class,array('data'=>1))
            ->add('productId', HiddenType::class,array(
                    'data' =>$productId,
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('save', SubmitType::class, array('label' => 'Enregistrer'))
            ->getForm();
    }

    public function carLodgmentForm($resa,$productId){
        return $this->createFormBuilder($resa)
            ->add('manyDays', CheckboxType::class, array(
                    'label'    => 'Plusieurs jours ?',
                    'required' => false,
            ))
            ->add('dateUnique', TextType::class,array(
                    'required' => false,
                    'attr' => array(
                    'readonly' => true,
            ),))
            ->add('dateRange', TextType::class,array(
                    'required' => false,
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('dateBegin', HiddenType::class,array(
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('dateEnd', HiddenType::class,array(
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('quantity', HiddenType::class,array(
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('productId', HiddenType::class,array(
                    'data' =>$productId,
                    'attr' => array(
                    'readonly' => true,
                    'hidden' => true,
            ),))
            ->add('save', SubmitType::class, array('disabled'=>true,'label' => 'Réserver'))
            ->getForm();

    }

    public function reservationForm($prod){
        $resa = array();
        if($prod->getProductType()->getName()=="Activity"){
            return $this->activityForm($resa,$prod->getId());
        }
        else{
            return $this->carLodgmentForm($resa,$prod->getId());
        }

    }


    public function createInvoiceReservations($resasSession,$firstname,$lastname,$email){
        $em = $this->getDoctrine()->getManager();
        $invoice = new Invoice();
        $invoice->setDate(new \Datetime());
        $invoice->setCode("fakecodetest");
        $invoice->setCustomerFirstName($firstname);
        $invoice->setCustomerLastName($lastname);
        $invoice->setCustomerEmail($email);
        $em->persist($invoice);
        
        foreach ($resasSession as $resaSession ) {
            $resa = new Reservation();
            $product = $em->getRepository('AppBundle:Product')->find($resaSession['productId']);
            $resa->setProduct($product);
            $resa->setDateBegin(new \Datetime($resaSession['dateBegin']));
            $resa->setDateEnd(new \Datetime($resaSession['dateEnd']));
            $resa->setQuantity($resaSession['quantity']);
            $resa->setInvoice($invoice);
            $invoice->addReservation($resa);
            
            $em->persist($resa);
        }
        $em->flush();
        return $invoice;
    }

    public function paymentForm(){
        return $this->get('form.factory')
          ->createNamedBuilder('payment-form')
          ->add('firstname',TextType::class)
          ->add('lastname',TextType::class)
          ->add('email',EmailType::class)
          ->add('token', HiddenType::class, [
            'constraints' => [new Assert\NotBlank()],
          ])
          ->add('submit', SubmitType::class, array('label' => 'Confirmer et Payer'))
          ->getForm();
    }


    public function sendMailConfirmationReservation($reservations){
        $message = (new \Swift_Message('Confirmation réservation'))
        ->setFrom('kaligana@mail.com')
        ->setTo($email)
        ->setBody(
            $this->renderView(
                // app/Resources/views/Emails/registration.html.twig
                'payment/mail_invoice.html.twig',
                array('reservations' => $reservation)
            ),
            'text/html'
        );
        $this->get('mailer')->send($message);
    }

    
}
