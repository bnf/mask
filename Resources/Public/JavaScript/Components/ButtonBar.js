define([
      'lit',
      'lit/directives/class-map',
      'TYPO3/CMS/Core/Ajax/AjaxRequest',
      'TYPO3/CMS/Backend/Tooltip',
      'TYPO3/CMS/Backend/Modal',
      'jquery',
      'TYPO3/CMS/Backend/Element/IconElement'
    ],
    function ({html, LitElement, nothing}, {classMap}, AjaxRequest, Tooltip, Modal, $) {

      class ButtonBarElement extends LitElement {

        static properties = {
          element: { type: Object, attribute: false },
          showMessage: { type: Function, attribute: false },
          openEdit: { type: Function, attribute: false },
          openDeleteDialog: { type: Function, attribute: false },
          language: { type: Object, attribute: false },
          table: { type: String },
          loading: { state: true },
        };

        constructor() {
          super();
          this.loading = false;
          this.initialized = false;
        }

        createRenderRoot() {
          return this;
        }

        render() {
          if (!this.element) {
            return nothing;
          }

          const {element, table, language, loading, toggleIcon} = this;
          const {tooltip} = language;

          return html`
            <div class="${table + '-' + element.key + '-bar'} mask-elements__btn-group">
              <div class="btn-group">

                <a class="btn btn-default" @click=${(e) => {this.hideTooltip(e); this.openFluidCodeModal(element)}} data-bs-toggle="tooltip" title=${tooltip.html}>
                  <typo3-backend-icon identifier="sysnote-type-2" size="small"></typo3-backend-icon>
                </a>

                <a class="btn btn-default" @click=${(e) => {this.hideTooltip(e); this.openEdit(table, element)}} data-bs-toggle="tooltip" title=${tooltip.editElement}>
                  <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
                </a>

                ${table === 'tt_content' ? html`
                  <a
                     class="btn btn-default ${classMap({'disable-pointer': loading})}"
                     @click=${(e) => {const action = this.element.hidden ? 'enable' : 'hide'; this.hideTooltip(e); this.toggleVisibility(action)}}
                     data-bs-toggle="tooltip"
                     title=${element.hidden ? tooltip.enableElement : tooltip.disableElement}
                  >
                    <typo3-backend-icon identifier=${toggleIcon} size="small"></typo3-backend-icon>
                  </a>

                  <a class="btn btn-default" @click=${(e) => {this.hideTooltip(e); this.openDeleteDialog(element)}} data-bs-toggle="tooltip" title=${tooltip.deleteElement}>
                    <typo3-backend-icon identifier="actions-edit-delete" size="small"></typo3-backend-icon>
                  </a>
                ` : nothing}

              </div>
            </div>
          `
        }

        firstUpdated() {
          if (!this.element) {
            return nothing;
          }
          Tooltip.initialize(`.${this.table}-${this.element.key}-bar [data-bs-toggle="tooltip"]`, {
            delay: {
              'show': 500,
              'hide': 100
            },
            trigger: 'hover',
            container: 'body'
          });
        }

        get toggleIcon() {
          if (this.loading) {
            return 'spinner-circle-dark';
          }
          return this.element?.hidden ? 'actions-edit-unhide' : 'actions-edit-hide';
        }

        toggleVisibility() {
          this.loading = true;
          (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_toggle_visibility)).post({element: this.element})
              .then(
                  async response => {
                    const res = await response.resolve();
                    this.loading = false;
                    this.showMessages(res);
                    this.dispatchEvent(new CustomEvent("toggle"));
                  }
              )
        }

        hideTooltip(event) {
          Tooltip.hide(event.currentTarget);
        }

        openFluidCodeModal(element) {
          const url = new URL(TYPO3.settings.ajaxUrls.mask_html, window.location.origin);
          url.searchParams.append('key', element.key);
          url.searchParams.append('table', this.table);

          Modal.advanced({
            type: Modal.types.ajax,
            size: Modal.sizes.large,
            title: 'Example Fluid Code for element: ' + element.label,
            content: url.href
          });
        }
      }

      window.customElements.define('mask-button-bar', ButtonBarElement);

      return {ButtonBarElement};
    }
);
