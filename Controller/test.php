<?php

namespace FMI\ChamssBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use FMI\ChamssBundle\Entity\Contact;
use FMI\ChamssBundle\Form\CommentType;
use FMI\ChamssBundle\Form\ContactType;

use FMI\ChamssBundle\Entity\Comment;

class FormsController extends Controller
{
    /**
     * @Route(
     *  "/accueil",
     *  name="fmi_chamss_accueil"
     * )
     */
    public function AccueilAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $articles = $em->getRepository('FMIChamssBundle:Article')->getActiveArticles();
        
        return $this->render('FMIChamssBundle:Forms:accueil.html.twig', array(
            'articles' => $articles
        ));
    }
    
    /**
     * @Route(
     *  "/article/{slug}",
     *  name="fmi_chamss_article"
     * )
     */
    public function PresentationAction(Request $request, $slug)
    {
        $em = $this->getDoctrine()->getManager();
        $article = $em->getRepository('FMIChamssBundle:Article')
                      ->findOneBy(['slug' => $slug]);
        if (!$article){
            throw  $this->createNotFoundException('Unable to find article.');
        }
        
        $comments = $em->getRepository('FMIChamssBundle:Comment')
                       ->getCommentsForArticle($article->getId());
        $com = new Comment();
        $formComment = $this->createForm(CommentType::class, $com);
        $formComment->handleRequest($request);
        if ($formComment->isSubmitted()){
            $com->setArticle($article);
            $em->persist($com);
            $em->flush();
            return $this->redirect($this->generateUrl('fmi_chamss_article', array(
                'slug' => $article->getSlug()))
            );
        }
        return $this->render('FMIChamssBundle:Forms:presentation.html.twig', array(
            'article'     => $article,
            'comments'    => $comments,
            'form' => $formComment->createView(),
        ));
    }
    
    
    /**
     * @Route("/contact", name="fmi_chamss_contact")
     */
    public function ContactAction(Request $request)
    {
        $contact       = new Contact();
        $flashMessage  = $this->get('flash_messages');
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);
        if ($form->isSubmitted()){
            if ($form->isValid()){
               $datas = $form->getData();
               $message = \Swift_Message::newInstance()
               ->setSubject($datas->getObject())
               ->setFrom($datas->getEmail())
               ->setTo($this->container->getParameter('blogger_blog.emails.contact_email'))
               ->setBody($this->renderView('FMIChamssBundle:Dashboard:contactEmail.text.twig', array('contact' => $datas)));
               $this->get('mailer')->send($message);
               $em->persist($datas);
               $em->flush();
               $flashMessage->addSuccess('La demande a été envoyé avec succes.');
            }
        }
        
        return $this->render('FMIChamssBundle:Forms:contact.html.twig', array(
             'form' => $form->createView()
        ));
    }  

}
