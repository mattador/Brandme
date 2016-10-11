<h3>Oportunidades esperando revisión</h3>
{% for opportunity in campaigns %}
    <div class="row">
        <div class="col-md-2">
            <img src="{{ opportunity['logo'] }}">
        </div>
        {{ form("", "method":"post", "autocomplete" : "off") }}
        <div class="col-md-8">
            <table class="col-md-8 margin-bottom-20">
                <tr>
                    <td>Anunciante</td>
                    <td>{{ opportunity['email'] }}</td>
                </tr>
                <tr>
                    <td>Nombre de Campaña</td>
                    <td>{{ opportunity['campaign_name'] }}</td>
                </tr>
                <tr>
                    <td>Nombre de Oportunidad</td>
                    <td>{{ opportunity['opportunity_name'] }}</td>
                </tr>
                <tr>
                    <td>Budget</td>
                    <td>${{ currency(opportunity['budget']) }} USD</td>
                </tr>
                <tr>
                    <td>Descripción</td>
                    <td>{{ opportunity['description'] }}</td>
                </tr>
                <tr>
                    <td>Requerimientos</td>
                    <td>{{ opportunity['requirements'] }}</td>
                </tr>
                <tr>
                    <td>Creador Ideal</td>
                    <td>{{ opportunity['ideal_creator'] }}</td>
                </tr>
                <tr>
                    <td>Creado</td>
                    <td>{{ opportunity['created_at'] }}</td>
                </tr>
            </table>
            <br>
            <textarea class="form-control" name="rejection_message">{{ rejection_message }}</textarea>

            <p class="alert-warning margin-top-10">Si decides rechazar la oportunidad se debe incluir un motivo para
                el anunciante que no excede 100 caracteres.</p>
            <br>
            <button class="btn green" type="submit" name="approve"
                    value="{{ opportunity['id_campaign_opportunity'] }}">
                {{ _("Approve") }}
            </button>
            <button class="btn red" type="submit" name="reject">
                {{ _("Reject") }}
            </button>
            <input type="hidden" name="id_opportunity" value="{{ opportunity['id_campaign_opportunity'] }}">
            {{ end_form() }}
        </div>

    </div>
<hr>
{% endfor %}
