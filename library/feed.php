<?php
namespace Goteo\Library {

	use Goteo\Core\Model,
        Goteo\Library\Text;

	/*
	 * Clase para loguear eventos
	 */
    class Feed {

        public
            $id,
            $title, // titulo entrada o nombre usuario
            $url = null, // enlace del titulo
            $image = null, // enlace del titulo
            $scope, // ambito del evento (public, admin)
            $type, // tipo de evento  ($public_types , $admin_types)
            $timeago, // el hace tanto
            $date, // fecha y hora del evento
            $html, // contenido del evento en codigo html
            $unique = false, // si es un evento unique, no lo grabamos si ya hay un evento con esa url
            $text,  // id del texto dinamico
            $params,  // (array serializado en bd) parametros para el texto dinamico
            $user, // usuario asociado al evento
            $project, // proyecto asociado al evento
            $node; // nodo asociado al evento

        static public $admin_types = array(
            'all' => array(
                'label' => 'Todo',
                'color' => 'light-blue'
            ),
            'admin' => array(
                'label' => 'Administrador',
                'color' => 'red'
            ),
            'user' => array(
                'label' => 'Usuario',
                'color' => 'blue'
            ),
            'project' => array(
                'label' => 'Proyecto',
                'color' => 'light-blue'
            ),
            'money' => array(
                'label' => 'Transferencias',
                'color' => 'violet'
            ),
            'system' => array(
                'label' => 'Sistema',
                'color' => 'grey'
            )
        );

        static public $public_types = array(
            'goteo' => array(
                'label' => 'Goteo'
            ),
            'projects' => array(
                'label' => 'Proyectos'
            ),
            'community' => array(
                'label' => 'Comunidad'
            )
        );

        static public $color = array(
            'user' => 'blue',
            'project' => 'light-blue',
            'blog' => 'grey',
            'news' => 'grey',
            'money' => 'violet',
            'relevant' => 'red',
            'comment' => 'green',
            'update-comment' => 'grey',
            'message' => 'green',
            'system' => 'grey',
            'update' => 'grey'
        );

        static public $page = array(
            'user' => '/user/profile/',
            'project' => '/project/',
            'blog' => '/blog/',
            'news' => '/news/',
            'relevant' => '',
            'comment' => '/blog/',
            'update-comment' => '/project/',
            'message' => '/project/',
            'system' => '/admin/',
            'update' => '/project/'
        );

        /*
        public $subjects = array(
            'user' => 'el usuario', // + item
            'project' => 'el proyecto', // + item
            'blog' => 'el blog', // + item
            'transaction' => 'la transaccion', // + item
            'campaign' => 'la campaña', // + item
            'node' => 'el nodo' // + item
        );

        public $action = array(
            'register' => 'se ha registrado',   // no mas target
            'publish' => 'ha publicado',        // target = un post
            'reach' => 'ha alcanzado',          // target = minimum/optimum
            'expire' => 'caduca en',   //
            'invest' => 'ha aportado',   //
            'message' => 'ha escrito',   // target = message
            'comment' => 'ha comentado',   // target = comment
            'insert' => 'ha hecho nuevo',   // target = tabla ¬ registro
            'update' => 'ha modificado',   // target = tabla ¬ registro
            'delete' => 'ha borrado',   // target = tabla ¬ registro
            'translate' => 'ha traducido',   // target = tabla ¬ registro
            'execute' => 'ha ejecutado',   // target = operacion (cargo, cancelacion, rollback, fail)
            'made' => 'ha hecho',   // target = subaccion exitosa
            'tried' => 'ha intentado'   // target = subaccion fallida
        );
        */

		/**
		 *  Metodo para sacar los eventos
         *
         * @param string $type  tipo de evento (public: columnas goteo, proyectos, comunidad;  admin: categorias de filtro)
         * @param string $scope ambito de eventos (public | admin)
         * @return array list of items
		 */
		public static function getAll($type = 'all', $scope = 'public') {

            $list = array();

            try {
                $values = array(':scope' => $scope);

                $sqlType = '';
                if ($type != 'all') {
                    $sqlType = " AND feed.type = :type";
                    $values[':type'] = $type;
                }

                $sql = "SELECT
                            feed.id as id,
                            feed.title as title,
                            feed.url as url,
                            feed.image as image,
                            DATE_FORMAT(feed.datetime, '%H:%i %d|%m|%Y') as date,
                            feed.datetime as timer,
                            feed.html as html
                        FROM feed
                        WHERE feed.scope = :scope $sqlType
                        ORDER BY datetime DESC
                        LIMIT 99
                        ";

                $query = Model::query($sql, $values);
                foreach ($query->fetchAll(\PDO::FETCH_CLASS, __CLASS__) as $item) {

                    //hace tanto
                    $item->timeago = self::time_ago($item->timer);

                    $list[] = $item;
                }
                return $list;
            } catch (\PDOException $e) {
                throw new Exception('FATAL ERROR SQL: ' . $e->getMessage() . "<br />$sql<br /><pre>" . print_r($values, 1) . "</pre>");
            }
		}

		/**
		 *  Metodo para grabar eventos
         *
         *  Los datos del evento estan en el objeto
         *
         *
         * @param array $errors
         *
         * @access public
         * @return boolean true | false   as success
         *
		 */
		public function add(&$errors = array()) {

            if (empty($this->scope)) $this->scope = 'admin';
            if (empty($this->type)) $this->type = 'system';


            // primero, verificar si es unique, no duplicarlo
            if ($this->unique === true) {
                $query = Model::query("SELECT id FROM feed WHERE url = :url AND scope = :scope AND type = :type",
                    array(
                    ':url' => $this->url,
                    ':scope' => $this->scope,
                    ':type' => $this->type
                ));
                if ($query->fetchColumn(0) != false) {
                    return true;
                }
            }

  			try {
                $values = array(
                    ':title' => $this->title,
                    ':url' => $this->url,
                    ':image' => $this->image,
                    ':scope' => !empty($this->scope) ? $this->scope : 'admin' ,
                    ':type' => !empty($this->type) ? $this->type : 'system',
                    ':html' => $this->html
                );

				$sql = "INSERT INTO feed
                            (id, title, url, scope, type, html, image)
                        VALUES
                            ('', :title, :url, :scope, :type, :html, :image)
                        ";
				if (Model::query($sql, $values)) {
                    return true;
                } else {
                    $errors[] = "Ha fallado $sql con <pre>" . print_r($values, 1) . "</pre>";
                    return false;
                }
                
			} catch(\PDOException $e) {
                $errors[] = 'Error sql al grabar el contenido de la pagina. ' . $e->getMessage();
                return false;
			}

		}
        
        /**
         * Metodo para transformar un TIMESTAMP en un "hace tanto"
         * 
         * 
         */
        public static function time_ago($date,$granularity=1) {

            $retval = '';
            $date = strtotime($date);
            $ahora = time();
            $difference = $ahora - $date;
            $periods = array('decada' => 315360000,
                'año' => 31536000,
                'mes' => 2628000,
                'semana' => 604800,
                'dia' => 86400,
                'hora' => 3600,
                'minuto' => 60,
                'segundo' => 1);

            foreach ($periods as $key => $value) {
                if ($difference >= $value) {
                    $time = floor($difference/$value);
                    $difference %= $value;
                    $retval .= ($retval ? ' ' : '').$time.' ';
                    $retval .= (($time > 1) ? $key.'s' : $key);
                    $granularity--;
                }
                if ($granularity == '0') { break; }
            }

            return empty($retval) ? 'nada' : $retval;
        }


        /**
         *  Genera codigo html para enlace o texto dentro de feed
         *
         */
        public static function item ($type = 'system', $label = 'label', $id = null) {

            // si llega id es un enlace
            if (isset($id)) {
                return '<a href="'.self::$page[$type].$id.'" class="'.self::$color[$type].'" target="_blank">'.$label.'</a>';
            } else {
                return '<span class="'.self::$color[$type].'">'.$label.'</span>';
            }


        }

        /**
         *  Genera codigo html para feed público
         *
         *  segun tenga imagen, ebnlace, titulo, tipo de enlace
         *
         */
        public static function subItem ($item) {

            $content = '<div class="subitem">';

           // si enlace -> título como texto del enlace
           if (!empty($item->url)) {
                // si imagen -> segun enlace:
                if (!empty($item->image)) {

                    if (substr($item->url, 0, 5) == '/user') {
                        $content .= '<div class="content-avatar">
                        <a href="'.$item->url.'" class="avatar"><img src="/image/'.$item->image.'/32/32/1" /></a>
                        <a href="'.$item->url.'" class="username">'.$item->title.'</a><br/>
                        <span class="datepub">Publicado hace '.$item->timeago.'</span>
                        </div>';
                    } else {
                        $content .= '<div class="content-image">
                        <a href="'.$item->url.'" class="image"><img src="/image/'.$item->image.'/90/60/1" /></a>
                        <a href="'.$item->url.'" class="project light-blue">'.$item->title.'</a>
                        <span class="datepub">Publicado hace '.$item->timeago.'</span>
                        </div>';
                    }
                } else {
                    // solo titulo con enlace
                    $content .= '<div class="content-title">
                        <h5 class="light-blue"><a href="'.$item->url.'" class="project light-blue">'.$item->title.'</a></h5>
                        <span class="datepub">Publicado hace '.$item->timeago.'</span>
                   </div>';
                }
           } else {
               // solo el timeago
               $content .= '<span class="datepub">Publicado hace '.$item->timeago.'</span>';
           }

           // y lo que venga en el html
           $content .= '<div class="content-pub">'.$item->html.'</div>';

           $content .= '</div>';

           return $content;
        }
        /* MAQUETACION
         *
               <!-- entrada blog con autor (con imagen, titulo y enlace) -->
                <div class="subitem">
                    <!-- avatar y nombre del autor -->
                    <div class="content-avatar">
                        <a href="/user/olivier" class="avatar">
                            <img src="/image/119/43/43/1" />
                        </a>
                        <a href="/user/olivier" class="username">Olivier</a><br/>
                        <span class="datepub">Publicado hace 2 horas</span>
                    </div>
                    <!--  -->
                    <div class="content-pub">
                    <span class="blue">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s</span>, when an <a class="light-blue" href="#">unknown printer</a> took a galley of type and scrambled it to make a type specimen book. It has <span class="light-blue">Twittometro</span> not only five centuries, but also the leap into electronic typesetting.
                    </div>
                </div>


               <!-- item entrada blog (con titulo y enlace) -->
                <div class="subitem">
                    <!-- titulo y enlace -->
                    <div class="content-title">
                        <h5 class="light-blue">Felicitamos al proyecto Canal Alpha</h5>
                        <span class="datepub">Publicado hace 2 horas</span>
                   </div>
                    <!-- // titulo y enlace -->
                   <div class="content-pub">Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy <a href="#" class="blue">text ever since the 1500s</a>, when an unknown printer took a galley of type and <span class="red">scrambled it to make a type specimen book</span>. It has <a class="violet" href="#">survived not only </a>five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It <span class="light-blue">was popularised in the 1960s</span> with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p>
                   </div>
                </div>
         *
         *
                <!-- proyecto con imagen, titulo y enlace -->
                <div class="subitem">
                    <div class="content-image">
                        <a href="/user/olivier" class="image">
                            <img src="/image/119/90/60/1" />
                        </a>
                        <a href="/user/olivier" class="project light-blue">TodoJunto LetterPress</a><br/>
                        <span class="datepub">Publicado hace 2 horas</span>
                    </div>
                    <div class="content-pub">
                    Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's <span class="violet">standard dummy text ever since </span>the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen.
                    </div>
                </div>

                <!-- evento sin título ni enlace -->
                <div class="subitem">
                   <span class="datepub">Publicado hace 2 horas</span>
                   <div class="content-pub">
                   Lorem Ipsum is simply dummy text of the printing and typesetting industry.
                   </div>
                </div>
         *
         *
                <!-- usuario con imagen, titulo y enlace -->
                <div class="subitem">
                    <div class="content-avatar">
                        <a href="/user/olivier" class="avatar">
                            <img src="/image/119/24/24/1" />
                        </a>
                        <a href="/user/olivier" class="username">Andres P.</a><br/>
                        <span class="datepub">Publicado hace 2 d�as</span>
                    </div>
                    <div class="content-pub">
                    Lorem Ipsum is simply <span class="light-blue">dummy </span>text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text <span class="grey">ever</span>.
                    </div>
                </div>
         * 
         */

    }
}