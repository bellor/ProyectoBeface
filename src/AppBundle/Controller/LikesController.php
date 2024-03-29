<?php
namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Publication;
use AppBundle\Entity\Likes;

class LikesController extends Controller
{

    /**
     * Guarda los likes de publicaciones que el usuario hace
     * Se usa AJAX, el id se recibe por URL en una llamada AJAX
     *
     * @param null $id
     * @return Response
     */
    public function likeAction($id = null)
    {
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        $publication_repo = $em->getRepository('AppBundle:Publication');
        $publication = $publication_repo->find($id);

        //check if the user already likes the publication or not!
        $likes = $em->getRepository('AppBundle:Likes')->findBy(
            array('user' => $user, 'publication' => $publication));
        if(!$likes)
        {
            $likes = new Likes();
            $likes->setUser($user);
            $likes->setPublication($publication);
            $em->persist($likes);
            $flush = $em->flush();
            if($flush == null) {
                $notification = $this->get('app.notification_service');
                $notification->set($publication->getUser(), 'like', $user->getId(), $publication->getId());
                $status = 'Te gusta esta publicación!';
            } else {
                $status = 'No se ha podido guardar el Me Gusta';
            }
        }
        else{
            $status = 'Ya le diste Like';
        }

        return new Response($status);
    }

    /**
     * Elimina los likes de publicaciones que el usuario hace
     * Se usa AJAX, el id publication se recibe por URL en una llamada AJAX
     *
     * @param null $id
     * @return Response
     */
    public function dislikeAction($id = null)
    {
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        $likes_repo = $em->getRepository('AppBundle:Likes');
        $likes = $likes_repo->findOneBy(array(
            'user' => $user,
            'publication' => $id
        ));

        //remove notification when user don't like anymore
        $notification = $em->getRepository('AppBundle:Notification')->findOneBy(array(
            'user' => $user,
            'extra' => $id
        ));
        if($notification){
            $em->remove($notification);
        }

        $em->remove($likes);
        $flush = $em->flush();

        if($flush == null) {
            $status = 'Ya no te gusta esta publicación!';
        } else {
            $status = 'No se ha podido quitar el Me Gusta';
        }

        return new Response($status);
    }

    /**
     * Carga vista con lista de publicaciones que le han gustado al usuario
     *
     * @param Request $request
     * @param null $nick
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function likesListAction(Request $request, $nick = null)
    {
        $em = $this->getDoctrine()->getManager();

        if($nick != null) {
            $user_repo = $em->getRepository('AppBundle:User');
            $user = $user_repo->findOneBy(array(
                'nick' => $nick
            ));
        } else {
            $user = $this->getUser();
        }

        if(empty($user) || !is_object($user)) {
            return $this->redirect($this->generateUrl('home_publications'));
        }

        $user_id = $user->getId();

        $dql = "SELECT l FROM AppBundle:Likes l WHERE l.user = $user_id ORDER BY l.id DESC";
        $query = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
            5 //numero de registros por paginas
        );

        return $this->render('AppBundle:Likes:likeslist.html.twig', array(
            'profile_user' => $user,
            'likeslist' => $pagination
        ));

    }

}

