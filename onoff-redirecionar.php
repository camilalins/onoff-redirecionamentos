<?php
/**
 * Plugin Name: OnOff Redirecionar
 * Description: Crie links personalizados com redirecionamento ou abertura incorporada, quando permitida pelo destino.
 * Version: 1.1.5
 * Author: Camila Lins
 * Author URI: https://onoffcriativos.com.br
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * License: GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: onoff-redirecionar
 */
if (!defined('ABSPATH')) { exit; }

final class OnOff_Redirecionamentos {
    const OPT = 'onoff_redirecionamentos_links';
    const MENU = 'onoff-redirecionamentos';

    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_onoff_save_redirect', array(__CLASS__, 'save'));
        add_action('admin_post_onoff_delete_redirect', array(__CLASS__, 'delete'));
        add_action('template_redirect', array(__CLASS__, 'redirect'), 0);
    }

    private static function links() {
        $links = get_option(self::OPT, array());
        return is_array($links) ? $links : array();
    }

    public static function menu() {
        add_menu_page('OnOff Redirecionar', 'Redirecionar', 'manage_options', self::MENU, array(__CLASS__, 'page'), 'dashicons-randomize', 58);
    }

    private static function return_url($notice) {
        return add_query_arg(array('page' => self::MENU, 'onoff_notice' => $notice), admin_url('admin.php'));
    }

    public static function save() {
        if (!current_user_can('manage_options')) { wp_die('Sem permissao.'); }
        check_admin_referer('onoff_save_redirect');
        $raw_slug = isset($_POST['onoff_slug']) ? sanitize_text_field(wp_unslash($_POST['onoff_slug'])) : '';
        $slug = sanitize_title(trim($raw_slug, '/ '));
        $url = isset($_POST['onoff_url']) ? esc_url_raw(trim(wp_unslash($_POST['onoff_url']))) : '';
        $mode = isset($_POST['onoff_mode']) && sanitize_key(wp_unslash($_POST['onoff_mode'])) === 'embed' ? 'embed' : 'redirect';
        $code = isset($_POST['onoff_code']) && (int) $_POST['onoff_code'] === 301 ? 301 : 302;
        $parts = wp_parse_url($url);
        if (!$slug || strpos($slug, '/') !== false || !$parts || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), array('http', 'https'), true)) {
            wp_safe_redirect(self::return_url('invalid')); exit;
        }
        // Evita colisao com o painel, rotas padrao e arquivos da instalacao.
        $reserved = array('wp-admin','wp-content','wp-includes','wp-json','wp-login','wp-login-php','xmlrpc-php','feed','robots-txt','sitemap-xml');
        if (in_array($slug, $reserved, true)) {
            wp_safe_redirect(self::return_url('reserved')); exit;
        }
        $homehost = wp_parse_url(home_url(), PHP_URL_HOST);
        if ($homehost && strcasecmp($homehost, $parts['host']) === 0) {
            // Links do mesmo site podem causar loops de redirecionamento.
            wp_safe_redirect(self::return_url('local')); exit;
        }
        // Nao substituir silenciosamente uma rota existente do WordPress.
        // Links ja criados pelo plugin permanecem editaveis.
        $links = self::links();
        if (!isset($links[$slug]) && (get_page_by_path($slug) || get_page_by_path($slug, OBJECT, 'post'))) {
            wp_safe_redirect(self::return_url('collision')); exit;
        }
        $links[$slug] = array('url' => $url, 'code' => $code, 'mode' => $mode);
        update_option(self::OPT, $links, false);
        wp_safe_redirect(self::return_url('saved')); exit;
    }

    public static function delete() {
        if (!current_user_can('manage_options')) { wp_die('Sem permissao.'); }
        $slug = isset($_POST['onoff_slug']) ? sanitize_title(wp_unslash($_POST['onoff_slug'])) : '';
        check_admin_referer('onoff_delete_' . $slug);
        $links = self::links();
        unset($links[$slug]);
        update_option(self::OPT, $links, false);
        wp_safe_redirect(self::return_url('deleted')); exit;
    }

    public static function redirect() {
        if (is_admin() || wp_doing_ajax() || (defined('REST_REQUEST') && REST_REQUEST)) { return; }
        $request_path = wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $home_path = wp_parse_url(home_url('/'), PHP_URL_PATH);
        $home_path = '/' . trim((string) $home_path, '/');
        $request_path = '/' . ltrim(rawurldecode((string) $request_path), '/');
        if ($home_path !== '/') {
            if (strpos($request_path, $home_path . '/') !== 0) { return; }
            $request_path = substr($request_path, strlen($home_path));
        }
        $slug = trim($request_path, '/');
        // Apenas uma unica parte no caminho; nao intercepta paginas e subrotas.
        if ($slug === '' || strpos($slug, '/') !== false) { return; }
        $links = self::links();
        $key = sanitize_title($slug);
        if (!isset($links[$key]) || $slug !== $key) { return; }
        $entry = $links[$key];
        if (!is_array($entry)) { return; }
        $url = $entry['url'] ?? '';
        $code = isset($entry['code']) && (int) $entry['code'] === 301 ? 301 : 302;
        if (!wp_http_validate_url($url)) { return; }
        if (($entry['mode'] ?? 'redirect') === 'embed') {
            self::render_embed($url);
            exit;
        }
        nocache_headers();
        wp_redirect($url, $code, 'OnOff Redirecionar');
        exit;
    }


    private static function render_embed($url) {
        // O navegador preserva a URL local apenas se o destino permitir abertura em iframe.
        // Cabeçalhos X-Frame-Options / CSP do destino podem impedir o conteúdo.
        nocache_headers();
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        header('X-Robots-Tag: noindex, nofollow', true);
        $title = get_bloginfo('name');
        ?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo esc_html($title); ?></title>
<style>html,body{height:100%;margin:0;background:#f4f6fb;font-family:system-ui,-apple-system,Segoe UI,sans-serif}iframe{border:0;position:fixed;inset:0;width:100%;height:100%;background:#fff}.onoff-note{position:fixed;z-index:5;right:16px;bottom:16px;max-width:360px;padding:13px 16px;border-radius:12px;background:#14213d;color:white;box-shadow:0 8px 28px #0003;font-size:13px}.onoff-note a{color:#a7ccff}.onoff-note button{float:right;margin-left:10px;background:none;border:0;color:white;cursor:pointer;font-size:18px}</style></head><body>
<iframe title="<?php echo esc_attr($title); ?>" src="<?php echo esc_url($url); ?>" allow="clipboard-read; clipboard-write" referrerpolicy="strict-origin-when-cross-origin"></iframe>
<div class="onoff-note" id="onoff-note"><button type="button" onclick="document.getElementById('onoff-note').remove()" aria-label="Fechar">×</button>Se a página aparecer em branco ou mostrar bloqueio, o serviço de destino não permite incorporação. <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">Abrir diretamente</a>.</div>
</body></html>
        <?php
    }

    public static function page() {
        if (!current_user_can('manage_options')) { return; }
        $links = self::links();
        ksort($links);
        $notice = isset($_GET['onoff_notice']) ? sanitize_key(wp_unslash($_GET['onoff_notice'])) : '';
        $messages = array('saved' => 'Redirecionamento salvo.', 'deleted' => 'Redirecionamento excluido.', 'invalid' => 'Informe um caminho e uma URL http(s) validos.', 'reserved' => 'Este caminho e reservado pelo WordPress.', 'local' => 'Para evitar loops, use uma URL de destino de outro dominio.', 'collision' => 'Ja existe uma pagina ou publicacao com este caminho. Escolha outro.');
        ?>
        <div class="wrap">
          <h1>OnOff Redirecionar</h1>
          <p>O dominio e identificado automaticamente: <strong><?php echo esc_html(home_url('/')); ?></strong></p>
          <?php if ($notice && isset($messages[$notice])) : ?><div class="notice <?php echo in_array($notice, array('saved','deleted'), true) ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo esc_html($messages[$notice]); ?></p></div><?php endif; ?>
          <div style="max-width:850px;background:#fff;border:1px solid #dcdcde;padding:20px 24px;margin:20px 0;border-radius:8px">
            <h2>Criar ou atualizar atalho</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
              <input type="hidden" name="action" value="onoff_save_redirect">
              <?php wp_nonce_field('onoff_save_redirect'); ?>
              <table class="form-table" role="presentation">
                <tr><th scope="row"><label for="onoff_slug">Caminho curto</label></th><td><input type="text" required id="onoff_slug" name="onoff_slug" placeholder="cobranca" class="regular-text" pattern="[A-Za-z0-9À-ÿ_-]+"><p class="description">Exemplo: <code>cobranca</code> cria <code><?php echo esc_html(home_url('/cobranca')); ?></code>. Acentos sao convertidos para letras sem acento.</p></td></tr>
                <tr><th scope="row"><label for="onoff_url">URL de destino</label></th><td><input type="url" required id="onoff_url" name="onoff_url" placeholder="https://script.google.com/macros/s/.../exec" class="large-text"></td></tr>
                <tr><th scope="row"><label for="onoff_mode">Modo de abertura</label></th><td><select name="onoff_mode" id="onoff_mode"><option value="redirect">Redirecionar (abre o destino)</option><option value="embed">Envelopar (manter endereço, se permitido)</option></select><p class="description">O modo envelopado utiliza iframe. O Google Apps Script pode bloquear essa incorporação por segurança; nesse caso, não é possível manter o endereço apenas com este plugin.</p></td></tr>
                <tr><th scope="row"><label for="onoff_code">Código HTTP (modo redirecionamento)</label></th><td><select name="onoff_code" id="onoff_code"><option value="302">302 — Temporario (recomendado)</option><option value="301">301 — Permanente</option></select><p class="description">Use 302 se o destino puder mudar, pois 301 pode ficar armazenado em cache no navegador.</p></td></tr>
              </table>
              <?php submit_button('Salvar redirecionamento'); ?>
            </form>
          </div>
          <h2>Links cadastrados</h2>
          <table class="widefat striped" style="max-width:1100px"><thead><tr><th>Link curto</th><th>Destino</th><th>Modo</th><th>Tipo</th><th>Acoes</th></tr></thead><tbody>
          <?php if (!$links) : ?><tr><td colspan="5">Nenhum link cadastrado.</td></tr><?php else : foreach ($links as $slug => $entry) :
            $short = home_url('/' . $slug);
            if (!is_array($entry) || empty($entry['url'])) { continue; }
            ?><tr><td><a href="<?php echo esc_url($short); ?>" target="_blank" rel="noopener noreferrer"><strong><?php echo esc_html($short); ?></strong></a></td><td style="max-width:430px;overflow-wrap:anywhere"><a href="<?php echo esc_url($entry['url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($entry['url']); ?></a></td><td><?php echo ($entry['mode'] ?? 'redirect') === 'embed' ? 'Envelopado (iframe)' : 'Redirecionamento'; ?></td><td><?php echo ($entry['mode'] ?? 'redirect') === 'embed' ? '—' : (int) ($entry['code'] ?? 302); ?></td><td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('Excluir este redirecionamento?');"><input type="hidden" name="action" value="onoff_delete_redirect"><input type="hidden" name="onoff_slug" value="<?php echo esc_attr($slug); ?>"><?php wp_nonce_field('onoff_delete_' . $slug); ?><button type="submit" class="button button-secondary">Excluir</button></form></td></tr><?php endforeach; endif; ?>
          </tbody></table>
          <p class="description">Desenvolvido por <a href="https://onoffcriativos.com.br" target="_blank" rel="noopener noreferrer">Camila Lins — OnOff Criativos</a>. Contato: <a href="mailto:gmlins06@gmail.com">gmlins06@gmail.com</a>.</p>
          <p class="description">No modo redirecionamento, a URL muda para o destino. No modo envelopado, a URL permanece no seu domínio somente quando o site de destino autoriza incorporação em iframe. Para alterar um link existente, cadastre novamente o mesmo caminho.</p>
        </div>
        <?php
    }
}
OnOff_Redirecionamentos::init();
