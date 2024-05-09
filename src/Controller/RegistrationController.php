<?php

namespace App\Controller;
use App\Form\LoginType;
use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Service\TwilioService;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/registe", name="app_register")
     */
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, SessionInterface $session, TwilioService $twilioService): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $formData->getUsername()]);
            if ($existingUser) {
                $errorMessage = 'Username already exists.';
                $this->addFlash('error', $errorMessage);
                return $this->redirectToRoute('app_register');
            } else {
                $otp = sprintf('%06d', mt_rand(0, 999999));
                $twilioService->sendWhatsappOTP($formData->getTelephone(), $otp);
                $session->set('username', $formData->getUsername());
                $session->set('otp', $otp);
                
                $user->setUsername($formData->getUsername());
                $user->setTelephone($formData->getTelephone());
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );
                $user->setVerification('Pending');

                $entityManager->persist($user);
                $entityManager->flush();
                
                $this->addFlash('success', 'Your registration has been successfully added. You can login now!');
                return $this->redirectToRoute('app_verify');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * @Route("/verify", name="app_verify")
     */
    public function verify(Request $request, SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        $msg = "";
        $otpFromForm = $request->request->get('otp');

        if ($session->get('otp') !== null && $session->get('username') !== null) {
            $sessionOtp = $session->get('otp');
            if (empty($otpFromForm)) {
                $msg = "";
            } else {
                if ($otpFromForm == $sessionOtp) {
                    $sessionUsername = $session->get('username');
                    $userRepository = $entityManager->getRepository(User::class);
                    $user = $userRepository->findOneBy(['username' => $sessionUsername]);
                    if ($user) {
                        $user->setVerification('Verified');
                        $entityManager->persist($user);
                        $entityManager->flush();
                        $msg = 'Account verified successfully you can login.';
                        // You can uncomment the line below if you want to redirect after successful verification
                        $this->addFlash('success', $msg);
                        return $this->redirectToRoute('app_login');
                    } else {
                        $this->addFlash('danger', 'Account not  verified successfully register again or check you whatssap.');
                        return $this->redirectToRoute('registe');
                    }
                } else {
                    $msg = 'Verification code is incorrect.';
                }
            }
        } else {
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('registration/verify.html.twig', ['message' => $msg]);
    }

    /**
     * @Route("/logn", name="logn")
     */
    public function login(Request $request, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $msg = "";
        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $repository = $entityManager->getRepository(User::class);
            $login = $repository->findOneBy([
                'username' => $formData['username'],
                'password' => $formData['password'],
            ]);
            
            
            if ($login !== null) {
                if ($login->getVerification() == "Pending") {
                    $telephone = $login->getTelephone();
                    $otp = sprintf('%06d', mt_rand(0, 999999));
                    // Assuming you have a Twilio service available, replace 'sendWhatsappOTP' with the appropriate method
                    // $this->twilioService->sendWhatsappOTP($telephone, $otp);
                    $session->set('username', $formData['username']);
                    $session->set('otp', $otp);
                    return $this->redirectToRoute('verify');
                } else {
                    $msg = "Your account has been verified, and you will be redirected to your dashboard page.";
                    // You can uncomment the line below if you want to redirect to the dashboard
                    // return $this->redirectToRoute('dashboard');
                }
            
            }
             else {
                $msg = "Incorrect Username/Password";
            }
        }
        
        return $this->render('registration/login.html.twig', [
            'form' => $form->createView(),
            'message' => $msg,
        ]);
    }
}
