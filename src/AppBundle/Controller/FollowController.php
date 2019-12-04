<?php
namespace AppBundle\Controller {
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use AppBundle\Entity\User; // Da acceso a la Entidad User
    use AppBundle\Entity\Follow; // Da acceso a la Entidad Follow
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;// Permite usar el método Response
    use Symfony\Component\HttpFoundation\Session\Session; // Permite usar sesiones

    class FollowController extends Controller
    {

/* OBJETO SESSIÓN
 * Para activar las sesiones inicializamos la variable e incluimos
 * en ella el objeto Session()
 * No olvidar dar acceso al componenete de Symfony
 * Session() permitirá usar los mensajes FLASHBAG
 */

private $session;

public function __construct()
{
    $this->session = new Session();
}
/*********************************************************************/
/* MÉTODO EJECUCIÓN AJAX PARA HACER FOLLOW ***************************/
        /**
         * Persiste en la entidad Follow los datos del usuario en la sesion y
         * el usuario que se sigue que se determina mediante una peticion request
         *
         * @param Request $request
         * @return Response
         */
        public function followAction(Request $request)
        {
            //En una variable creamos una respuesta para saber si nuestra petición es AJAX
            $isAjax = $request->isXmlHttpRequest();

            //Si lo es mandamos a people
            if (!$isAjax) {
                return $this->redirect("people");
            }

            $user = $this->getUser();// Capturamos los datos de nuestro usuario con el que estamos logueados

            $followed_id = $request->get('followed'); // Almaceno en la variable $followed_id al usuario que voy a seguir

            $em = $this->getDoctrine()->getManager();// Cargo el Entity Manager

            $user_repo = $em->getRepository('AppBundle:User'); // Cargo el repositorio de la entidad Usuario

            $followed = $user_repo->find($followed_id); // Busco al usuario al que queremos seguir


            $follow = new Follow();// Creo un objeto a partir de la entidad

            $follow->setUser($user);// Usuario que va a seguir

            $follow->setFollowed($followed);// Usuario Seguido

            $em->persist($follow);// Persistimos la query
            $flush = $em->flush();// Incluimos los datos en la BD

            if ($flush == null){
                /* Sistema de notificaciones....................................*/
                $notification = $this->get('app.notification_service');
                $notification->set($followed, 'follow', $user->getId());
                $status = "Ahora estas siguiendo a este Usuario";
            }else{
                $status = "No se ha podido seguir a este Usuario";
            }

            return new Response($status); // para usar Response es necesario incluir el componente

        }


        /*********************************************************************/
        /* MÉTODO EJECUCIÓN AJAX PARA HACER UNFOLLOW *************************/
        /**
         * Borra un registro de seguimiento usando entidad Follow, usuario logeado y
         * parametro request de id de usuario que se sigue.
         *
         * @param Request $request
         * @return Response
         */
        public function unfollowAction(Request $request)
        {
            //En una variable creamos una respuesta para saber si nuestra petición es AJAX
            $isAjax = $request->isXmlHttpRequest();

            //Si lo es mandamos a people
            if (!$isAjax) {
                return $this->redirect("people");
            }

            $user = $this->getUser();// Capturamos los datos de nuestro usuario con el que estamos logueados
            $followed_id = $request->get('followed');// Almaceno en la variable $followed_id al usuario que voy a seguir

            $em = $this->getDoctrine()->getManager();// Cargo el Entity Manager

            $follow_repo = $em->getRepository('AppBundle:Follow');// Sacamos el objeto que tiene el seguidor y seguido

            // Sacamos el registro de la tabla con las siguientes condiciones
            $followed = $follow_repo->findOneBy(array(
                'user' => $user,
                'followed' => $followed_id
            ));
            
            $em->remove($followed);// Elimina el registro del EM
            $flush = $em->flush();// Incluimos los datos en la BD

            if ($flush == null){
                $status = "Has dejado de seguir a este Usuario";
            }else{
                $status = "No se ha podido dejar de seguir a este Usuario";
            }

            return new Response($status);// para usar Response es necesario incluir el componente

        }


        /* MÉTODO PARA MOSTRAR LOS PERFILES QUE SIGUE UN PERFIL  *************/
        /**
         * Carga vista con lista de usuarios que esta siguiendo el usuario logueado
         * o con lista de usuarios que siguen al usuario logueado
         * para diferenciar se usa parametro type a través de URL
         *
         * @param Request $request
         * @param null $nick
         * @param null $type
         * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
         */
        public function followsListAction(Request $request, $nick = null, $type = null)
        {
            $em = $this->getDoctrine()->getManager();// Cargo el Entity Manager

            if($nick != null) {
                $user_repo = $em->getRepository('AppBundle:User');// Cargo el repositorio de la entidad Usuario
                $user = $user_repo->findOneBy(array(
                    'nick' => $nick
                ));
            } else {
                $user = $this->getUser();// Capturamos los datos de nuestro usuario con el que estamos logueados
            }

            if(empty($user) || !is_object($user)) {
                return $this->redirect($this->generateUrl('home_publications'));
            }

            $user_id = $user->getId();

            //Consultamos en la base de datos
            if($type == 'following') {
                $dql = "SELECT f FROM AppBundle:Follow f WHERE f.user = $user_id ORDER BY f.id DESC";
            } elseif($type == 'followers') {
                $dql = "SELECT f FROM AppBundle:Follow f WHERE f.followed = $user_id ORDER BY f.id DESC";
            } else {
                return $this->redirect($this->generateUrl('home_publications'));
            }

            $query = $em->createQuery($dql);

            $paginator = $this->get('knp_paginator');
            $pagination = $paginator->paginate(
                $query,
                $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
                5 //numero de registros por paginas
            );

            //Redireccionamos a nuestra plantilla twig
            return $this->render('AppBundle:Follow:followslist.html.twig', array(
                'type' => $type,
                'profile_user' => $user,
                'followslist' => $pagination
            ));

        }
    }

}
