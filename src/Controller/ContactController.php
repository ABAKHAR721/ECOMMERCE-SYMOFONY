<?php
 
namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    /**
     * @Route("/contact", name="app_contact")
     */
    public function index( Request $request,MailerInterface $mailer): Response
    {
        $form=$this->createForm(ContactType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            
            $data=$form->getData();
            $emailuser = (string)$data['email'];
            $message=$data['message'];
            $subject=$data['subject'];
            
            $email=(new Email())
            ->from($emailuser)
            ->to('tavenger47@gmail.com')
            ->subject($subject)
            ->text($message);
            
            $mailer->send($email);
            $this->addFlash('success','mail send successfuly !!!');
            return $this->redirectToRoute('app_contact');

        }
        return $this->renderForm('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
