<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request; // Permite usar el método Request
use Symfony\Component\HttpFoundation\Response; // Permite usar el método Response

class NotificationController extends Controller
{
    /* MÉTODO PARA MOSTRAR LAS NOTIFICACIONES DE UN PERFIL  ********************************************/
    /**
     * Muestra vista con las notificaciones del usuario logueado
     * y marca notificaciones como leidas
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function notificationsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();// Cargo el Entity Manager

        $user = $this->getUser();// Sacamos el usuario del cual gestionar las NOTIFICACIONES
        $user_id = $user->getId();

        // Se usa id de usuario en la consulta ya que la entidad tiene toString que devuelve nombre del usuario
        // notificaciones que van dirigidas a un usuario indicado en este caso el usuario logueado
        $dql = "SELECT n FROM AppBundle:Notification n WHERE n.user = $user_id ORDER BY n.id DESC";
        // Cargamos la query
        $query = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
            5 //numero de registros por paginas
        );

        $dqlMarkAsRead = "UPDATE n FROM AppBundle:Notification n WHERE n.user = $user_id SET () VALUES ()";

        /* NOTIFICACIONES ******************************************************************************/
    /*
     * Iniciamos el servicio 'app.notification_service' declarado dentro de 'app\config\services.yml'
     * ver el método 'read' dentro de 'src\AppBundle\Services\NotificationService.php'
     * ( Ver 'app\config\services.yml', 'src\AppBundle\Services\NotificationService.php' y
     * 'src\AppBundle\Controller\NotificationController.php' )
     */

        // marca notificaciones como leidas
    $notification = $this->get('app.notification_service');
        $notification->read($user);// Esto marcará como leidas nuestras publicaciones

        // indicamos la vista
        return $this->render('AppBundle:Notification:notifications.html.twig', array(
            'profile_user' => $user,
            'notifications' => $pagination
        ));

    }


    /* MÉTODO AJAX PARA CONTAR EL NÚMERO DE NOTIFICACIONES SIN LEER ************************************/

    /**
     * Devuelve el número de notificaciones para el usuario logueado
     * mediante AJAX se llama a la ruta de este método y el valor de respuesta
     * se muestra en el layout
     *
     * @param Request $request
     * @return Response
     */
    public function countNotificationsAction(Request $request)
    {
        //En una variable creamos una respuesta para saber si nuestra petición es AJAX
        $isAjax = $request->isXmlHttpRequest();

        //Si lo es mandamos a notifications
        if (!$isAjax) {
          
            return $this->redirect("../notifications");
        }

        $em = $this->getDoctrine()->getManager(); // Cargo el Entity Manager

        $notification_repo = $em->getRepository("AppBundle:Notification");// Extraemos el repositorio de las Notification de su entidad

        // Buscamos la coincidencia según usuario y readed igual a '0' (sin leer)
        $notifications = $notification_repo->findBy(array(
            'user' => $this->getUser(),
            'readed' => 0
        ));

        // Enviamos el contador de notaficaciones no leidas
        return new Response(count($notifications));
        /* Para ver la respuesta colocar la url indicada dentro del ROUTING */
    }
}
