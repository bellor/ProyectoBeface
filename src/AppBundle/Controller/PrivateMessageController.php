<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // Permite usar el método Response
use Symfony\Component\HttpFoundation\Session\Session; // Permite usar sesiones

use AppBundle\Entity\PrivateMessage; // Da acceso a la Entidad PrivateMessage
use AppBundle\Form\PrivateMessageType; // Da acceso al Formulario PrivateMessageType
use AppBundle\Entity\User; // Da acceso a la Entidad User

class PrivateMessageController extends Controller
{

    /* OBJETO SESSIÓN
     * Para activar las sesiones inicializamos la variable e incluimos
     * en ella el objeto Session()
     * No olvidar dar acceso al componenete de Symfony
     * Session() permitirá usar los mensajes FLASHBAG
     */

    private $session;

    /**
     * UserController constructor.
     */
    public function __construct()
    {
        $this->session = new Session();
    }


    /* MÉTODO PARA LA HOME MENSAJES PRIVADOS ***********************************************************/
    /**
     * Renderiza en vista el formulario para enviar Mensajes Privados para ello
     * usa clase PrivateMessageType para definir los campos del formulario
     * Procesa el formulario y guarda los datos, también pasa los
     * mensajes recibidos a la vista para que se muestren
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function indexPrivateMessageAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();   // Cargamos el Entity Manager de Doctrine

        $user = $this->getUser();

        $private_message = new PrivateMessage();// Creamos un objeto PrivateMessage()

        /*
           * Usamos la propiedad 'empty_data' para pasar la variable
           * ( viene de 'src\AppBundle\Controller\PrivateMessageController.php',
           * 'src\AppBundle\form\PrivateMessageType.php',
           * 'src\BackendBundle\Repository\UserRepository.php' y
           * 'src\BackendBundle\Resources\config\doctrine\user.orm.yml')
           */
        $form = $this->createForm(PrivateMessageType::class, $private_message, array(
            'empty_data' => $user
        ));

        // Pasamos los datos de la petición del formulario y los pase a la entidad
        $form->handleRequest($request);

// Comprobamos si se envió el Mensaje y si fué válido
        if($form->isSubmitted()) {
            if($form->isValid()) {

                $user_media_route = 'uploads/media/'.$user->getId().'_usermedia';

                /* SUBIMOS LA IMAGEN *************************************************************************/
                $file = $form['image']->getData();
                if (!empty($file) && $file != null) {
                    $ext = $file->guessExtension(); //capturamos la extensión del fichero

                    // comprobamos la extensión del fichero
                    // Damos nombre al archivo
                    if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                        // Subimos el archivo al hosting
                        $file_name = $user->getId().'_imgpmessage_'.time().'.'.$ext;
                        // Guardamos el nombre del fichero en la BD
                        $file->move($user_media_route.'/pmessages/images', $file_name);

                         // Guardamos el nombre del fichero en la BD
                        $private_message->setImage($file_name);
                    } else {
                        $private_message->setImage(null);
                    }
                } else {
                    $private_message->setImage(null);
                }

                 // subimos los datos usando los setters
                $private_message->setEmitter($user);
                $private_message->setCreatedAt(new \DateTime("now"));
                $private_message->setReaded(0);

                 // persistimos los datos dentro de Doctirne
                $em->persist($private_message);
                 // guardamos los datos persistidos dentro de la BD
                $flush = $em->flush();

                // Si se guardan correctamente los datos en la BD
                if ($flush == null) {
                    $status = 'El mensaje privado se ha enviado correctamente';
                } else {
                    $status = 'Error al enviar el mensaje privado';
                }

            } else {
                $status = 'El mensaje privado no se ha enviado';
            }

            /* MOSTRAR FLASHBAG ****************************************************************************/
            // Si seenvió el formulario mostrar las FlashBag
            // Gseneramos los mensajes FLASH (necesario activar las sesiones)
            $this->session->getFlashBag()->add("status", $status);
            return $this->redirectToRoute('private_message_index');
        }

        $private_messages = $this->getPrivateMessages($request);
        $this->setReadedPrivateMessages($em, $user); // marca mensajes como leidos

        return $this->render('AppBundle:PrivateMessage:index_private_message.html.twig', array(
            'form' => $form->createView(),
            'private_messages' => $private_messages,
            'type' => 'received'
        ));
    }

    /* MÉTODO PARA MOSTRAR LA VISTA DE LOS MENSAJES ENVIADOS ***************************************************/

    /**
     * Muestra vista con los mensajes enviados por el usuario logueado
     *
     * @param Request $request
     * @return Response
     */
    public function sendedAction(Request $request)
    {
        $private_messages = $this->getPrivateMessages($request, "sended");

        return $this->render('AppBundle:PrivateMessage:sended.html.twig', array(
            'private_messages' => $private_messages,
            'type' => 'sended'
        ));

    }

    /* MÉTODO PARA LA HOME MENSAJES PRIVADOS ENVIADOS***************************************************/
// Necesita de la función 'public function getPrivateMessages($request, $type = null)'
    /**
     * Devuelve los mensajes privados recibidos o enviados dependiendo
     * del parámetro type
     *
     * @param $request
     * @param null $type si es "sended" devuelve mensajes enviados
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getPrivateMessages($request, $type = null)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $user_id = $user->getId();

        if($type == "sended") {
            $dql = "SELECT p FROM AppBundle:PrivateMessage p WHERE"
            . " p.emitter = $user_id AND p.active = 1 ORDER BY p.id DESC";
        } else {
            $dql = "SELECT p FROM AppBundle:PrivateMessage p WHERE"
            . " p.receiver = $user_id AND p.active = 1 ORDER BY p.id DESC";
        }

        $query = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
            5 //numero de registros por paginas
        );

        return $pagination;
    }


    /* MÉTODO AUXILIAR CONTAR MENSAJES NO LEIDOS *******************************************************/
    /**
     * Devuelve numero de mensajes privados no leidos
     * este método es llamado por AJAX
     *
     * @return Response
     */
    public function notReadedAction(Request $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if (!$isAjax) {
            
            return $this->redirect("../../private-message");
        }

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $pm_repo = $em->getRepository('AppBundle:PrivateMessage');
        $num_not_readed_pm = count($pm_repo->findBy(array(
            'receiver' => $user,
            'readed' => 0
        )));

        return new Response($num_not_readed_pm);
    }

    /* MÉTODO AUXILIAR CONVERTIR MENSAJES NO LEIDOS EN LEIDOS*******************************************/

    /**
     * Marca como leidos los mensajes privados del usuario logueado
     *
     * @param $em
     * @param $user
     * @return bool
     */
    private function setReadedPrivateMessages($em, $user)
    {
        $pm_repo = $em->getRepository('AppBundle:PrivateMessage');
        $private_messages = $pm_repo->findBy(array(
            'receiver' => $user,
            'readed' => 0
        ));

        foreach($private_messages as $msg) {
            $msg->setReaded(1);
            $em->persist($msg);
        }

        $flush = $em->flush();

        if ($flush == null) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /* MÉTODO  PARA ELIMINAR LOS MENSAJES PRIVADOS ******************************************/
    /**
     * Borrado de Mensajes privados
     *
     * @param null $id
     * @return Response
     */
    public function removeMessageAction($id = null)
    {
        $em = $this->getDoctrine()->getManager();//Extraemos el entity manager
        $private_messages_repo = $em->getRepository('AppBundle:PrivateMessage');//Extremos el repositorio de los mensajes de su entidad

        $private_messages = $private_messages_repo->find($id);//Buscamos el id del mensaje
        $user = $this->getUser();//Obtenemos el id de nuestro usuario

        if($user->getId() == $private_messages->getReceiver()->getId() || $user->getId() == $private_messages->getEmitter()->getId()) {
           
            $private_messages->setActive(false);
            $em->persist($private_messages);
            // persistimos la eliminación dentro de la bD
            $flush = $em->flush();

            // preparamos los mensajes informativos
            if ($flush == null){
                $status = 'El mensaje se ha borrado correctamente';
            } else {
                $status = 'El mensaje no se ha borrado';
            }
        } else {
            $status = 'El mensaje no se ha borrado';
        }

        // para usar el objeto response es necesario cargarlo al ppio del controlador
        return new Response($status);
    }

}

