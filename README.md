# OnOff Redirecionar

Plugin WordPress gratuito para criar links curtos no domínio do seu próprio site, com redirecionamento HTTP 301/302 ou incorporação por iframe quando o destino permitir.

**Autora:** Camila Lins  
**Site:** https://onoffcriativos.com.br  
**Contato:** gmlins06@gmail.com  
**Versão:** 1.1.5  
**Licença:** GPL-2.0-or-later

## Como funciona

Crie `seusite.com.br/cobranca` e associe esse caminho a um endereço externo, como um Google Apps Script. No modo **Redirecionar**, o navegador passa ao endereço de destino; no modo **Envelopar**, o navegador mantém seu domínio usando iframe, desde que o serviço de destino permita.

## Instalação

Faça o download da versão distribuída e instale em **Plugins → Adicionar plugin → Enviar plugin**. Ative e acesse o menu **Redirecionar** no painel WordPress.

## Segurança e limitações

- O modo iframe não contorna políticas `X-Frame-Options` ou `Content-Security-Policy` do destino.
- Não use o plugin como mecanismo de autenticação ou para ocultar sistemas vulneráveis.
- A configuração dos links requer permissão de administrador.
- Nenhuma telemetria ou contagem remota de uso está implementada nesta versão.

## Publicação

A versão 1.1.5 foi submetida ao diretório WordPress.org sob o slug `onoff-redirecionar` em 13/10/2026 e aguarda revisão manual.
