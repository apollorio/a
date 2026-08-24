<?php
/**
 * Create Event — Apollo About Editor (fragment).
 *
 * Replaces the plain #ev-about textarea. Hidden #ev-about keeps the
 * existing create-bridge contract (name="content" → post_content).
 *
 * @package Apollo\Event
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="field ap-about-editor-wrap" style="margin-bottom: 0;">
	<label class="field-label ap-ed-fieldlabel" for="apBody"><?php esc_html_e( 'Sobre o Evento', 'apollo-events' ); ?></label>
	<input type="hidden" id="ev-about" name="content" value="">

	<div class="ap-editor" id="apEditor">
		<div class="ap-ed-toolbar-wrap">
			<div class="ap-ed-toolbar" id="apToolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Formatação', 'apollo-events' ); ?>">
				<div class="ap-tgroup">
					<button type="button" class="ap-tbtn" data-cmd="undo" title="<?php esc_attr_e( 'Desfazer', 'apollo-events' ); ?>" aria-label="<?php esc_attr_e( 'Desfazer', 'apollo-events' ); ?>"><i class="ri-arrow-go-back-line"></i></button>
					<button type="button" class="ap-tbtn" data-cmd="redo" title="<?php esc_attr_e( 'Refazer', 'apollo-events' ); ?>" aria-label="<?php esc_attr_e( 'Refazer', 'apollo-events' ); ?>"><i class="ri-arrow-go-forward-line"></i></button>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<div class="ap-pop-wrap" data-pop="block">
						<button type="button" class="ap-tbtn ap-tbtn-wide" data-pop-trigger="block" aria-haspopup="true" aria-expanded="false">
							<i class="ri-paragraph" data-block-icon></i>
							<span class="ap-tw-label" data-block-label><?php esc_html_e( 'Normal', 'apollo-events' ); ?></span><i class="ri-arrow-down-s-line"></i>
						</button>
						<div class="ap-pop" role="menu">
							<div class="ap-pop-item is-selected" data-block="P"><i class="ri-paragraph"></i><?php esc_html_e( 'Normal', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-divider"></div>
							<div class="ap-pop-item ap-pop-h1" data-block="H1"><i class="ri-h-1"></i><?php esc_html_e( 'Título 1', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item ap-pop-h2" data-block="H2"><i class="ri-h-2"></i><?php esc_html_e( 'Título 2', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item ap-pop-h3" data-block="H3"><i class="ri-h-3"></i><?php esc_html_e( 'Título 3', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item ap-pop-h4" data-block="H4"><i class="ri-h-4"></i><?php esc_html_e( 'Título 4', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item ap-pop-h5" data-block="H5"><i class="ri-h-5"></i><?php esc_html_e( 'Título 5', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item ap-pop-h6" data-block="H6"><i class="ri-h-6"></i><?php esc_html_e( 'Título 6', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
						</div>
					</div>
				</div>
				<div class="ap-tgroup">
					<div class="ap-pop-wrap" data-pop="size">
						<button type="button" class="ap-tbtn" data-pop-trigger="size" title="<?php esc_attr_e( 'Tamanho do texto', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-font-size"></i></button>
						<div class="ap-pop ap-pop-fader" role="menu">
							<div class="fader-widget">
								<div class="fader-header">
									<span class="fader-label"><?php esc_html_e( 'Tamanho', 'apollo-events' ); ?></span>
									<div class="fader-readout"><span class="fr-val" id="apSizeFrVal">16.0</span><span class="unit">px</span></div>
								</div>
								<div class="fader-container">
									<input type="range" class="marks numbers" id="apSizeFader" min="0" max="1000" value="160" step="10" aria-label="<?php esc_attr_e( 'Tamanho', 'apollo-events' ); ?>" style="--val: 16%;">
								</div>
							</div>
						</div>
					</div>
					<div class="ap-pop-wrap" data-pop="leading">
						<button type="button" class="ap-tbtn" data-pop-trigger="leading" title="<?php esc_attr_e( 'Entrelinha', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-line-height"></i></button>
						<div class="ap-pop ap-pop-fader" role="menu">
							<div class="fader-widget">
								<div class="fader-header">
									<span class="fader-label"><?php esc_html_e( 'Entrelinha', 'apollo-events' ); ?></span>
									<div class="fader-readout"><span class="fr-val" id="apLeadFrVal">1.75</span><span class="unit">×</span></div>
								</div>
								<div class="fader-container">
									<input type="range" class="marks numbers" id="apLeadFader" min="100" max="300" value="175" step="5" aria-label="<?php esc_attr_e( 'Entrelinha', 'apollo-events' ); ?>" style="--val: 37.5%;">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<button type="button" class="ap-tbtn" data-cmd="bold" title="<?php esc_attr_e( 'Negrito', 'apollo-events' ); ?>" aria-pressed="false"><i class="ri-bold"></i></button>
					<button type="button" class="ap-tbtn" data-cmd="italic" title="<?php esc_attr_e( 'Itálico', 'apollo-events' ); ?>" aria-pressed="false"><i class="ri-italic"></i></button>
					<button type="button" class="ap-tbtn" data-cmd="underline" title="<?php esc_attr_e( 'Sublinhado', 'apollo-events' ); ?>" aria-pressed="false"><i class="ri-underline"></i></button>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<button type="button" class="ap-tbtn" data-cmd="blockquote" title="<?php esc_attr_e( 'Citação', 'apollo-events' ); ?>" aria-pressed="false"><i class="ri-double-quotes-l"></i></button>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<button type="button" class="ap-tbtn" data-cmd="insertOrderedList" title="<?php esc_attr_e( 'Lista numerada', 'apollo-events' ); ?>" aria-pressed="false"><i class="ri-list-ordered"></i></button>
					<button type="button" class="ap-tbtn" data-cmd="insertUnorderedList" title="<?php esc_attr_e( 'Lista com marcadores', 'apollo-events' ); ?>" aria-pressed="false"><i class="ri-list-unordered"></i></button>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<button type="button" class="ap-tbtn" data-cmd="outdent" title="<?php esc_attr_e( 'Diminuir indentação', 'apollo-events' ); ?>"><i class="ri-indent-decrease"></i></button>
					<button type="button" class="ap-tbtn" data-cmd="indent" title="<?php esc_attr_e( 'Aumentar indentação', 'apollo-events' ); ?>"><i class="ri-indent-increase"></i></button>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<div class="ap-pop-wrap" data-pop="color">
						<button type="button" class="ap-tbtn" data-pop-trigger="color" title="<?php esc_attr_e( 'Cor do texto', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false">
							<span class="ap-swatch-dot" data-color-preview style="background:currentColor"></span>
						</button>
						<div class="ap-pop ap-pop-swatches" role="menu">
							<div class="ap-swgrid" data-swatch-target="color">
								<button type="button" class="ap-sw" data-color="" style="background:var(--txt-color)" title="<?php esc_attr_e( 'Padrão', 'apollo-events' ); ?>"></button>
								<button type="button" class="ap-sw" data-color="#111111" style="background:#111111" title="Tinta"></button>
								<button type="button" class="ap-sw" data-color="#4a4a46" style="background:#4a4a46" title="Carvão"></button>
								<button type="button" class="ap-sw" data-color="#7d7566" style="background:#7d7566" title="Taupe"></button>
								<button type="button" class="ap-sw" data-color="#8a6d3b" style="background:#8a6d3b" title="Bronze"></button>
								<button type="button" class="ap-sw" data-color="#b08d57" style="background:#b08d57" title="Dourado"></button>
								<button type="button" class="ap-sw" data-color="#6b7a5e" style="background:#6b7a5e" title="Sálvia"></button>
								<button type="button" class="ap-sw" data-color="#425466" style="background:#425466" title="Marinho"></button>
								<button type="button" class="ap-sw" data-color="#7a3b3b" style="background:#7a3b3b" title="Bordô"></button>
								<button type="button" class="ap-sw" data-color="#a15c3e" style="background:#a15c3e" title="Terracota"></button>
								<button type="button" class="ap-sw" data-color="#c98b8b" style="background:#c98b8b" title="Rosa"></button>
								<button type="button" class="ap-sw" data-color="#ffffff" style="background:#ffffff" title="Branco"></button>
								<div class="ap-sw ap-sw-custom" title="<?php esc_attr_e( 'Personalizado', 'apollo-events' ); ?>">
									<i class="ri-add-line"></i>
									<input type="color" data-swatch-target="color" aria-label="<?php esc_attr_e( 'Cor do texto personalizada', 'apollo-events' ); ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ap-tgroup">
					<div class="ap-pop-wrap" data-pop="hl">
						<button type="button" class="ap-tbtn" data-pop-trigger="hl" title="<?php esc_attr_e( 'Destaque', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-mark-pen-line"></i></button>
						<div class="ap-pop ap-pop-swatches" role="menu">
							<div class="ap-swgrid" data-swatch-target="hl">
								<button type="button" class="ap-sw ap-sw-none" data-color="transparent" title="<?php esc_attr_e( 'Nenhum', 'apollo-events' ); ?>"><i class="ri-close-line"></i></button>
								<button type="button" class="ap-sw" data-color="#fdf1c8" style="background:#fdf1c8" title="Creme"></button>
								<button type="button" class="ap-sw" data-color="#fde8c9" style="background:#fde8c9" title="Areia"></button>
								<button type="button" class="ap-sw" data-color="#f4ddd0" style="background:#f4ddd0" title="Rosé"></button>
								<button type="button" class="ap-sw" data-color="#e3e8d3" style="background:#e3e8d3" title="Sálvia"></button>
								<button type="button" class="ap-sw" data-color="#dbe6ea" style="background:#dbe6ea" title="Névoa"></button>
								<button type="button" class="ap-sw" data-color="#e6ddf0" style="background:#e6ddf0" title="Lilás"></button>
								<button type="button" class="ap-sw" data-color="#eeeeee" style="background:#eeeeee" title="Cinza"></button>
								<div class="ap-sw ap-sw-custom" title="<?php esc_attr_e( 'Personalizado', 'apollo-events' ); ?>">
									<i class="ri-add-line"></i>
									<input type="color" data-swatch-target="hl" aria-label="<?php esc_attr_e( 'Cor de destaque personalizada', 'apollo-events' ); ?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<div class="ap-pop-wrap" data-pop="align">
						<button type="button" class="ap-tbtn" data-pop-trigger="align" title="<?php esc_attr_e( 'Alinhamento', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-align-left" data-align-icon></i></button>
						<div class="ap-pop" role="menu">
							<div class="ap-pop-item is-selected" data-align="Left"><i class="ri-align-left"></i><?php esc_html_e( 'Esquerda', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item" data-align="Center"><i class="ri-align-center"></i><?php esc_html_e( 'Centro', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item" data-align="Right"><i class="ri-align-right"></i><?php esc_html_e( 'Direita', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
							<div class="ap-pop-item" data-align="Full"><i class="ri-align-justify"></i><?php esc_html_e( 'Justificado', 'apollo-events' ); ?><i class="ri-check-line"></i></div>
						</div>
					</div>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<div class="ap-pop-wrap" data-pop="link">
						<button type="button" class="ap-tbtn" data-pop-trigger="link" title="<?php esc_attr_e( 'Inserir link', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-link"></i></button>
						<div class="ap-pop" role="menu">
							<div class="ap-pop-field">
								<input type="text" class="apollo-input" id="apLinkInput" placeholder="https://apollo.rio.br/…">
								<div class="ap-pop-field-row">
									<button type="button" class="btn btn-ghost btn-sm" id="apLinkRemove"><?php esc_html_e( 'Remover', 'apollo-events' ); ?></button>
									<button type="button" class="btn btn-primary btn-sm" id="apLinkApply"><?php esc_html_e( 'Inserir', 'apollo-events' ); ?></button>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="ap-tgroup">
					<button type="button" class="ap-tbtn" id="apImageBtn" title="<?php esc_attr_e( 'Inserir imagem', 'apollo-events' ); ?>"><i class="ri-image-add-line"></i></button>
					<input type="file" accept="image/*" id="apImageFile" class="ap-hidden-file">
				</div>
				<div class="ap-tgroup ap-tgroup-img" id="apImgTools">
					<div class="ap-tdiv"></div>
					<button type="button" class="ap-tbtn ap-img-tool" data-img-align="left" title="<?php esc_attr_e( 'Alinhar à esquerda', 'apollo-events' ); ?>"><i class="ri-align-left"></i></button>
					<button type="button" class="ap-tbtn ap-img-tool is-active" data-img-align="center" title="<?php esc_attr_e( 'Centralizar', 'apollo-events' ); ?>"><i class="ri-align-center"></i></button>
					<button type="button" class="ap-tbtn ap-img-tool" data-img-align="right" title="<?php esc_attr_e( 'Alinhar à direita', 'apollo-events' ); ?>"><i class="ri-align-right"></i></button>
					<div class="ap-pop-wrap ap-img-tool" data-pop="imgsize">
						<button type="button" class="ap-tbtn" data-pop-trigger="imgsize" title="<?php esc_attr_e( 'Tamanho da imagem', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-crop-line"></i></button>
						<div class="ap-pop" role="menu">
							<div class="ap-size-row">
								<div class="ap-size-opt" data-imgsize="s"><span class="ap-size-bar"></span><span>S</span></div>
								<div class="ap-size-opt is-selected" data-imgsize="m"><span class="ap-size-bar"></span><span>M</span></div>
								<div class="ap-size-opt" data-imgsize="l"><span class="ap-size-bar"></span><span>L</span></div>
								<div class="ap-size-opt" data-imgsize="full"><span class="ap-size-bar"></span><span><?php esc_html_e( 'Completo', 'apollo-events' ); ?></span></div>
							</div>
						</div>
					</div>
					<div class="ap-pop-wrap ap-img-tool" data-pop="alt">
						<button type="button" class="ap-tbtn" data-pop-trigger="alt" title="<?php esc_attr_e( 'Texto alternativo', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-braces-line"></i></button>
						<div class="ap-pop align-right" role="menu">
							<div class="ap-pop-field">
								<input type="text" class="apollo-input" id="apAltInput" placeholder="<?php esc_attr_e( 'Descreva esta imagem…', 'apollo-events' ); ?>">
								<div class="ap-pop-field-row">
									<button type="button" class="btn btn-primary btn-sm" id="apAltApply"><?php esc_html_e( 'Salvar', 'apollo-events' ); ?></button>
								</div>
							</div>
						</div>
					</div>
					<div class="ap-pop-wrap ap-img-tool ap-img-tool-full" data-pop="fit">
						<button type="button" class="ap-tbtn" data-pop-trigger="fit" title="<?php esc_attr_e( 'Ajuste e posição', 'apollo-events' ); ?>" aria-haspopup="true" aria-expanded="false"><i class="ri-focus-3-line"></i></button>
						<div class="ap-pop" role="menu">
							<div class="ap-size-row">
								<div class="ap-size-opt is-selected" data-fit="cover"><span><?php esc_html_e( 'Cobrir', 'apollo-events' ); ?></span></div>
								<div class="ap-size-opt" data-fit="contain"><span><?php esc_html_e( 'Conter', 'apollo-events' ); ?></span></div>
							</div>
							<div class="ap-pop-divider"></div>
							<div class="ap-size-row">
								<div class="ap-size-opt is-selected" data-pos="top"><span><?php esc_html_e( 'Topo', 'apollo-events' ); ?></span></div>
								<div class="ap-size-opt" data-pos="center"><span><?php esc_html_e( 'Centro', 'apollo-events' ); ?></span></div>
								<div class="ap-size-opt" data-pos="bottom"><span><?php esc_html_e( 'Base', 'apollo-events' ); ?></span></div>
							</div>
						</div>
					</div>
					<button type="button" class="ap-tbtn ap-img-tool ap-img-tool-full" id="apImgFillHeight" title="<?php esc_attr_e( 'Preencher altura do editor', 'apollo-events' ); ?>"><i class="ri-fullscreen-line"></i></button>
					<button type="button" class="ap-tbtn ap-img-tool" id="apImgReplace" title="<?php esc_attr_e( 'Substituir imagem', 'apollo-events' ); ?>"><i class="ri-refresh-line"></i></button>
					<button type="button" class="ap-tbtn ap-img-tool is-danger" id="apImgDelete" title="<?php esc_attr_e( 'Excluir imagem', 'apollo-events' ); ?>"><i class="ri-delete-bin-6-line"></i></button>
				</div>
				<div class="ap-tdiv"></div>
				<div class="ap-tgroup">
					<button type="button" class="ap-tbtn" data-cmd="clean" title="<?php esc_attr_e( 'Limpar formatação', 'apollo-events' ); ?>"><i class="ri-format-clear"></i></button>
				</div>
			</div>
		</div>

		<div
			class="ap-ed-body is-empty"
			id="apBody"
			contenteditable="true"
			role="textbox"
			aria-multiline="true"
			aria-label="<?php esc_attr_e( 'Sobre o Evento', 'apollo-events' ); ?>"
			data-placeholder="<?php esc_attr_e( 'Comece a escrever algo memorável…', 'apollo-events' ); ?>"
		></div>

		<div class="ap-ed-bottom">
			<span class="ap-ed-counter" id="apCounter" title="<?php esc_attr_e( 'Clique para alternar palavras / caracteres', 'apollo-events' ); ?>">0 caracteres</span>
			<div class="ap-ed-actions">
				<button type="button" class="btn btn-ghost btn-sm" id="apPreviewBtn"><i class="ri-eye-line"></i><span><?php esc_html_e( 'Visualizar', 'apollo-events' ); ?></span></button>
			</div>
		</div>
	</div>
</div>

<div class="ap-preview-overlay" id="apPreviewOverlay">
	<div class="ap-preview-modal" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Pré-visualização do conteúdo', 'apollo-events' ); ?>">
		<div class="ap-preview-head">
			<span class="ap-preview-title"><i class="ri-eye-line"></i> <?php esc_html_e( 'Pré-visualização', 'apollo-events' ); ?></span>
			<div class="ap-preview-head-actions">
				<button type="button" class="btn btn-ghost btn-sm" id="apPreviewToggleCode"><i class="ri-code-s-slash-line"></i><span><?php esc_html_e( 'Ver HTML', 'apollo-events' ); ?></span></button>
				<button type="button" class="btn btn-primary btn-sm" id="apPreviewCopy"><i class="ri-clipboard-line"></i><span><?php esc_html_e( 'Copiar HTML', 'apollo-events' ); ?></span></button>
				<button type="button" class="ap-tbtn" id="apPreviewClose" aria-label="<?php esc_attr_e( 'Fechar pré-visualização', 'apollo-events' ); ?>"><i class="ri-close-line"></i></button>
			</div>
		</div>
		<div class="ap-preview-body">
			<div class="ap-preview-render" id="apPreviewRender"></div>
			<pre class="ap-preview-code" id="apPreviewCode" hidden><code id="apPreviewCodeText"></code></pre>
		</div>
	</div>
</div>
<div class="ap-toast" id="apToast" role="status" aria-live="polite"><i class="ri-checkbox-circle-fill"></i><span id="apToastMsg"><?php esc_html_e( 'Pronto', 'apollo-events' ); ?></span></div>
