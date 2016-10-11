<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico"/>
    {{ get_title() }}
    {{ stylesheet_link("http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&amp;subset=all", false) }}
    {{ stylesheet_link("assets/global/plugins/font-awesome/css/font-awesome.min.css") }}
    {{ stylesheet_link("assets/global/plugins/simple-line-icons/simple-line-icons.min.css") }}
    {{ stylesheet_link("assets/global/css/components.css") }}
    {{ stylesheet_link("css/bootstrap.min.css") }}
    {{ stylesheet_link("css/bootstrap-theme.min.css") }}
    {{ stylesheet_link("assets/frontend/layout/css/style.css") }}
    {{ stylesheet_link("assets/frontend/layout/css/style-responsive.css") }}
    {{ stylesheet_link("assets/frontend/pages/css/portfolio.css") }}
    {{ stylesheet_link("assets/frontend/layout/css/themes/purple.css") }}
    {{ stylesheet_link("css/brandme/styles.css") }}

    {% if css_assets is not [] %}
        {% for a in css_assets %}
            {{ stylesheet_link(a) }}
        {% endfor %}
    {% endif %}
    {{ javascript_include('js/jquery-1.11.2.min.js') }}
    {{ javascript_include('js/bootstrap.min.js') }}
</head>
<body{% if bodyClass is defined %} class="{{ bodyClass }}"{% endif %}>
<nav class="navbar navbar-default navbar-brandme">
    <div class="container-fluid">
        <div class="navbar-header">
            <a href="/">
                <img src="/img/logo-brandme-login.jpg" alt="BrandMe" width="300px">
            </a>
        </div>
    </div>
</nav>
{{ flash.output() }}
{% if messages is defined and messages is not [] %}
    <ul class="alert alert-danger">
        {% for i, msg in messages %}
            <li>{{ _(msg) }}</li>
        {% endfor %}
    </ul>
{% endif %}
<div class="container content-container">
    <ul class="list-unstyled list-inline pull-left col-md-12">
        <li>
            <a target="new" href="/admin/oportunidades">
                Aprobar/Rechazar Oportunidades
            </a>
        </li>
        <li>|</li>
        <li>
            <a target="new" href="/admin/transaccion/crear">
                Abonar Dinero
            </a>
        </li>
        <li>|</li>
        <li>
            <a target="new" href="/admin/seguridad">
                Crear Access Temporal
            </a>
        </li>
    </ul>
    <br>
    <br>
    {{ content() }}
</div>
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-8">
                2015 © BrandMe. Todos los derechos reservados.
                <a href="terminos-y-condiciones"> Términos y Condiciones</a> |
                <a href="aviso-de-privacidad">Aviso de Privacidad</a>
            </div>
            <div class="col-md-4">
                <ul class="social-footer list-unstyled list-inline pull-right">
                    <li>
                        <a target="new" href="https://twitter.com/brandme">
                            <i class="fa fa-twitter"></i>
                        </a>
                    </li>
                    <li>
                        <a target="new" href="https://www.facebook.com/brandme">
                            <i class="fa fa-facebook"></i>
                        </a>
                    </li>
                    <li>
                        <a target="new" href="http://www.linkedin.com/company/ponme-tu-marca">
                            <i class="fa fa-linkedin"></i>
                        </a>
                    </li>
                    <li>
                        <a target="new" href="https://www.youtube.com/channel/UC-xugPKj2TCraqbTCNnme9w">
                            <i class="fa fa-youtube"></i>
                        </a>
                    </li>
                    <li>
                        <a target="new" href="https://plus.google.com/u/3/100604468693105319001">
                            <i class="fa fa-google-plus"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</footer>
{% if js_assets is not [] %}
    {% for a in js_assets %}
        {{ javascript_include(a) }}
    {% endfor %}
{% endif %}
</body>
</html>