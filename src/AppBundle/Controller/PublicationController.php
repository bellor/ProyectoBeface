<?php
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // Permite usar el método Response
use Symfony\Component\HttpFoundation\Session\Session; // Permite usar sesiones

use AppBundle\Form\PublicationType; // Da acceso al Formulario PublicationType
use AppBundle\Entity\Publication; // Da acceso a la Entidad Publication

class PublicationController extends Controller
{
    /* OBJETO SESSIÓN
   * Para activar las sesiones inicializamos la variable e incluimos
   * en ella el objeto Session()
   * No olvidar dar acceso al componenete de Symfony
   * Session() permitirá usar los mensajes FLASHBAG
   */
    private $session;

    /**
     * PublicationController constructor.
     */
    public function __construct()
    {
        $this->session = new Session();
    }

    /* MÉTODO PARA CREAR EL FORMULARIO Y REPRESENTARLO  ****************************************************************************/
    /**
     * Crea formulario con la clase PublicationType y lo renderiza en vista
     * Comprueba el formulario enviado y guarda los datos
     * Además pasa a la vista las publicaciones del timeline
     *
     * @param Request $request
     * @return $this
     */
    public function publicationsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);

        $form->handleRequest($request);
        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $user_media_route = 'uploads/media/'.$user->getId().'_usermedia';

                // upload image
                $file = $form['image']->getData();
                if (!empty($file) && $file != null) {
                    $ext = $file->guessExtension(); // obtencion de extension

                    if ($ext == 'jpg' || $ext == 'jpeg' || $ext == 'png' || $ext == 'gif') {
                        $file_name = $user->getId().'_imgpublication_'.time().'.'.$ext;
                        $file->move($user_media_route.'/publications/images', $file_name);

                        $publication->setImage($file_name);
                    } else {
                        $publication->setImage(null);
                    }
                } else {
                    $publication->setImage(null);
                }



                $publication->setUser($user);
                $publication->setCreatedAt(new \DateTime("now"));

                $em->persist($publication);
                $flush = $em->flush();

                if ($flush == null) {
                    $status = 'La publicación se ha creado correctamente !!';
                } else {
                    $status = 'Error al añadir la publicación !!';
                }

            } else {
                $status = 'La publicación no se ha creado, porque el formulario no es válido';
            }

            $this->session->getFlashBag()->add("status", $status);
            return $this->redirectToRoute('home_publications');
        } //issubit

        $publications = $this->getPublications($request);

        return $this->render('AppBundle:Publication:home.html.twig', array(
            'form' => $form->createView(),
            'publications' => $publications
        ));
    }


    /* MÉTODO AUXILIAR PARA EXTRAER LAS PUBLICACIONES PUBLICADAS ***************************************/
  /*
    /**
     * Recupera mediante los datos de usuario logueado sus publicaciones
     * y las publicaciones de las personas que sigue
     * para ello se usa una subconsulta en dql
     * se devuelve un objeto con los resultados paginados
     *
     * @param $request
     * @return \Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function getPublications($request) {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $publication_repo = $em->getRepository('AppBundle:Publication');
        $follow_repo = $em->getRepository('AppBundle:Follow');

        /*
         * SELECT texto FROM sn_publication WHERE user = 4 OR
         * user IN (SELECT followed FROM sn_follow WHERE user = 4);
         */

        $following = $follow_repo->findBy(array('user' => $user));

        $following_array = array();
        foreach($following as $follow) {
            $following_array[] = $follow->getFollowed();
        }

        $query = $publication_repo->createQueryBuilder('p')
        ->where('p.user = (:user_id) OR p.user IN (:following)')
        ->setParameter('user_id', $user->getId())
        ->setParameter('following', $following_array)
        ->orderBy('p.id', 'DESC')
        ->getQuery();

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // parametro request de paginacion y en que num de pagina empieza
            5 //numero de registros por paginas
        );

        return $pagination;
    }

    /* MÉTODO AJAX PARA ELIMINAR LAS PUBLICACIONES PUBLICADAS ******************************************/
    /**
     * Borrado de Publicaciones via AJAX a traves de URL
     * Solo el autor de la publicacion puede borrar sus publicaciones
     *
     * @param null $id
     * @return Response
     */
    public function removePublicationAction($id = null)
    {
        $em = $this->getDoctrine()->getManager();// Extraemos el entity manager
        $publication_repo = $em->getRepository('AppBundle:Publication');// Extraemos el repositorio de las publicaciones de su entidad

        $publication = $publication_repo->find($id);// Buscamos la publicación que entra por la url 'id'
        $user = $this->getUser();// Obtenemos el id de nuestro usuario


    /*
     * Creamos la condición que permita borrar solo las publicaciones que
     * hemos creado con nuestro usuario
     */

    if($user->getId() == $publication->getUser()->getId()) {
             // Eliminamos el objeto dentro de doctrine
        $em->remove($publication);
            // persistimos la eliminación dentro de la bD
        $flush = $em->flush();

            // preparamos los mensajes informativos
        if ($flush == null){
            $status = 'La publicación se ha borrado correctamente';
        } else {
            $status = 'La publicación no se ha borrado';
        }
    } else {
        $status = 'La publicación no se ha borrado';
    }

        // para usar el objeto response es necesario cargarlo al ppio del controlador
    return new Response($status);
}





/* MÉTODO PARA CONFIGURAR EL PERFIL DE USUARIO ***********************************************/
     /**
     * 
     *
     * @param Request $request
     * @param null $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
     public function configurePubliAction(Request $request, $id = null)
     {
        $em = $this->getDoctrine()->getManager();

        $publications_repo = $em->getRepository('AppBundle:Publication');

        $user = $this->getUser();


        return $this->render('AppBundle:Publication:admin_publi.html.twig', array(
            'publications' => $publications_repo->findAll()
        ));
    }



    /* MÉTODO AJAX PARA ELIMINAR LAS PUBLICACIONES PUBLICADAS ******************************************/
    /**
     * Borrado de Publicaciones via AJAX a traves de URL
     * Solo el autor de la publicacion puede borrar sus publicaciones
     *
     * @param null $id
     * @return Response
     */
    public function removePublicationAdminAction($id = null)
    {

        $em = $this->getDoctrine()->getManager();// Extraemos el entity manager
        $publication = $em->getRepository('AppBundle:Publication')->findOneBy(array(
            'id' => $id
            ));;// Extraemos el repositorio de las publicaciones de su entidad

        $user = $this->getUser();// Obtenemos el id de nuestro usuario


    /*
     * Creamos la condición que permita borrar solo las publicaciones que
     * hemos creado con nuestro usuario
     */

    if(!is_null($publication)) {
             // Eliminamos el objeto dentro de doctrine
        $em->remove($publication);
            // persistimos la eliminación dentro de la bD
        $flush = $em->flush();

            // preparamos los mensajes informativos
        if ($flush == null){
            $status = 'La publicación se ha borrado correctamente';
        } else {
            $status = 'La publicación no se ha borrado';
        }
    } else {
        $status = 'La publicación no se ha borrado';
    }

        // para usar el objeto response es necesario cargarlo al ppio del controlador
    return new Response($status);
}


}