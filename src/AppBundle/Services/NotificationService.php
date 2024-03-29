<?php
namespace AppBundle\Services {

    use AppBundle\Entity\Notification;

    class NotificationService
    {
        public $manager;

        /**
         * NotificationService constructor.
         * @param $manager
         */
        public function __construct($manager)
        {
            $this->manager = $manager;
        }

        /**
         * Guarda los datos para una notificación de actividad
         *
         * @param User $user usuario receptor de la notificacion
         * @param string $type tipo de notificacion
         * @param int $typeId id de usuario que realiza la acción
         * @param string $extra otra informacion que puede ser un id
         * @return bool
         */
        public function set($user, $type, $typeId, $extra = null)
        {
            $em = $this->manager;

            $notification = new Notification();
            $notification->setUser($user);
            $notification->setType($type);
            $notification->setTypeId($typeId);
            $notification->setReaded(0);
            $notification->setCreatedAt(new \DateTime("now"));
            $notification->setExtra($extra);

            $em->persist($notification);
            $flush =  $em->flush();

            if($flush == null) {
                $status =  true;
            } else {
                $status = false;
            }

            return $status;
        }

        /**
         * Marca notificaciones como leidas
         *
         * @param User $user usuario receptor de la notificacion
         * @return bool
         */
        public function read($user)
        {
            $em = $this->manager;
            $notification_repo = $em->getRepository("AppBundle:Notification");

            $notifications = $notification_repo->findBy(array('user' => $user));

            foreach($notifications as $notification){
                $notification->setReaded(1);
                $em->persist($notification);
            }
            $flush = $em->flush();

            if($flush == null) {
                return true;
            } else {
                return false;
            }
        }

    }
}