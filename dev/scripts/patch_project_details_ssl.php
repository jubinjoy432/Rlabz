<?php
$file = 'assets/js/project-details.js';
$content = file_get_contents($file);

$search1 = <<<'EOD'
        if (project.demoLink && project.demoLink !== '#') rows.push({ label: 'Demo Link', value: `<a href="${project.demoLink}" target="_blank" rel="noopener"><i class="fa-solid fa-external-link-alt"></i> Visit</a>` });
EOD;

$replace1 = <<<'EOD'
        if (project.demoLink && project.demoLink !== '#') rows.push({ label: 'Demo Link', value: `<a href="${project.demoLink}" target="_blank" rel="noopener"><i class="fa-solid fa-external-link-alt"></i> Visit</a>` });
        
        if (project.ssl && project.ssl.domain_url) {
            let sslText = `<a href="${project.ssl.domain_url}" target="_blank" rel="noopener" style="color:#10b981;"><i class="fa-solid fa-lock"></i> Secured</a>`;
            if (project.ssl.provider) sslText += ` by ${project.ssl.provider}`;
            rows.push({ label: 'SSL Certificate', value: sslText });
        }
EOD;

$content = str_replace($search1, $replace1, $content);
file_put_contents($file, $content);
echo "Done patching project-details.js!\n";
?>
