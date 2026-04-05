<?php

$files = [
    'rdv' => 'C:\\Users\\asus\\Downloads\\AutiCare_web\\templates\\admin\\pages\\rdv.html.twig',
    'seance' => 'C:\\Users\\asus\\Downloads\\AutiCare_web\\templates\\admin\\pages\\seance.html.twig',
    'planning' => 'C:\\Users\\asus\\Downloads\\AutiCare_web\\templates\\admin\\pages\\planning.html.twig'
];

foreach ($files as $type => $file) {
    if (!file_exists($file)) continue;

    $content = file_get_contents($file);

    // Identify the block starting at `<div class="row g-3 mb-4">` and ending at the closing `</div>` right before `<div class="row g-3 mb-4">` or `<div class="row g-3">`

    $regex = '/<div class="row g-3 mb-4">\s*<div class="col-6 col-xl-3">.*?<div class="stat-change up">.*?<\/div>\s*<\/div>\s*<\/div>\s*<\/div>/s';
    
    if ($type === 'rdv') {
        $replacement = <<<TWIG
<div class="row g-3 mb-4">
    {% set nb_total = rdvs|length %}
    {% set nb_confirmes = 0 %}
    {% set nb_planifies = 0 %}
    {% set nb_annules = 0 %}
    {% for item in rdvs %}
        {% if item.statutRdv == 'confirme' %}
            {% set nb_confirmes = nb_confirmes + 1 %}
        {% elseif item.statutRdv == 'planifiee' or item.statutRdv == 'planifiÃ©e' %}
            {% set nb_planifies = nb_planifies + 1 %}
        {% elseif item.statutRdv == 'annule' or item.statutRdv == 'annulÃ©' %}
            {% set nb_annules = nb_annules + 1 %}
        {% endif %}
    {% endfor %}

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Total RDV</div>
                    <div class="stat-value">{{ nb_total }}</div>
                </div>
                <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-chart-line"></i> base globale</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">ConfirmÃ©s</div>
                    <div class="stat-value">{{ nb_confirmes }}</div> 
                </div>
                <div class="stat-icon violet"><i class="fas fa-check-circle"></i></div>  
            </div>
            <div class="stat-change up"><i class="fas fa-thumbs-up"></i> actes validÃ©s</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">PlanifiÃ©s</div>
                    <div class="stat-value">{{ nb_planifies }}</div>
                </div>
                <div class="stat-icon pink"><i class="fas fa-clock"></i></div>   
            </div>
            <div class="stat-change text-warning"><i class="fas fa-hourglass-half"></i> en attente</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">AnnulÃ©s</div>
                    <div class="stat-value">{{ nb_annules }}</div>
                </div>
                <div class="stat-icon indigo"><i class="fas fa-times-circle"></i></div>
            </div>
            <div class="stat-change text-danger" style="color: var(--bs-danger);"><i class="fas fa-exclamation-circle"></i> Ã  reprogrammer</div>
        </div>
    </div>
</div>
TWIG;
    } elseif ($type === 'seance') {
        $replacement = <<<TWIG
<div class="row g-3 mb-4">
    {% set nb_total = seances|length %}
    {% set nb_confirmes = 0 %}
    {% set nb_planifies = 0 %}
    {% set nb_annules = 0 %}
    {% for item in seances %}
        {% if item.statutSeance == 'confirme' %}
            {% set nb_confirmes = nb_confirmes + 1 %}
        {% elseif item.statutSeance == 'planifiee' or item.statutSeance == 'planifiÃ©e' %}
            {% set nb_planifies = nb_planifies + 1 %}
        {% elseif item.statutSeance == 'annule' or item.statutSeance == 'annulÃ©' %}
            {% set nb_annules = nb_annules + 1 %}
        {% endif %}
    {% endfor %}

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Total SÃ©ances</div>
                    <div class="stat-value">{{ nb_total }}</div>
                </div>
                <div class="stat-icon purple"><i class="fas fa-calendar-check"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-chart-line"></i> base globale</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">ConfirmÃ©es</div>
                    <div class="stat-value">{{ nb_confirmes }}</div> 
                </div>
                <div class="stat-icon violet"><i class="fas fa-check-circle"></i></div>  
            </div>
            <div class="stat-change up"><i class="fas fa-thumbs-up"></i> sÃ©ances validÃ©es</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">PlanifiÃ©es</div>
                    <div class="stat-value">{{ nb_planifies }}</div>
                </div>
                <div class="stat-icon pink"><i class="fas fa-clock"></i></div>   
            </div>
            <div class="stat-change text-warning"><i class="fas fa-hourglass-half"></i> en attente</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">AnnulÃ©es</div>
                    <div class="stat-value">{{ nb_annules }}</div>
                </div>
                <div class="stat-icon indigo"><i class="fas fa-times-circle"></i></div>
            </div>
            <div class="stat-change text-danger" style="color: var(--bs-danger);"><i class="fas fa-exclamation-circle"></i> Ã  reprogrammer</div>
        </div>
    </div>
</div>
TWIG;
    } elseif ($type === 'planning') {
        $replacement = <<<TWIG
<div class="row g-3 mb-4">
    {% set nb_total = emplois|length %}
    {% set nb_matin = 0 %}
    {% set nb_apresmidi = 0 %}
    {% set nb_soir = 0 %}
    {% for item in emplois %}
        {% if item.trancheHoraire == 'matin' %}
            {% set nb_matin = nb_matin + 1 %}
        {% elseif item.trancheHoraire == 'apres_midi' %}
            {% set nb_apresmidi = nb_apresmidi + 1 %}
        {% elseif item.trancheHoraire == 'soir' or item.trancheHoraire == 'journee' %}
            {% set nb_soir = nb_soir + 1 %}
        {% endif %}
    {% endfor %}

    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Total CrÃ©neaux</div>
                    <div class="stat-value">{{ nb_total }}</div>
                </div>
                <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div class="stat-change up"><i class="fas fa-chart-line"></i> annÃ©e: {{ currentSchoolYear }}</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Matin</div>
                    <div class="stat-value">{{ nb_matin }}</div> 
                </div>
                <div class="stat-icon violet"><i class="fas fa-sun"></i></div>  
            </div>
            <div class="stat-change up"><i class="fas fa-clock"></i> crÃ©neaux dÃ©but</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">AprÃ¨s-midi</div>
                    <div class="stat-value">{{ nb_apresmidi }}</div>
                </div>
                <div class="stat-icon pink"><i class="fas fa-cloud-sun"></i></div>   
            </div>
            <div class="stat-change text-warning"><i class="fas fa-clock"></i> crÃ©neaux denses</div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-label">Soir & JournÃ©e</div>
                    <div class="stat-value">{{ nb_soir }}</div>
                </div>
                <div class="stat-icon indigo"><i class="fas fa-moon"></i></div>
            </div>
            <div class="stat-change text-info" style="color: var(--bs-info);"><i class="fas fa-clock"></i> crÃ©neaux d'appoint</div>
        </div>
    </div>
</div>
TWIG;
    }

    $newContent = preg_replace($regex, $replacement, $content, 1);
    file_put_contents($file, $newContent);
    echo "Updated $file\n";
}
