define([
      'lit',
      'jquery',
      'TYPO3/CMS/Mask/Contrib/FontIconPicker'
    ],
    function ({html, LitElement, nothing}, $) {
      class FontIconPickerElement extends LitElement {
        static properties = {
          element: { type: Object, attribute: false },
          label: { type: String },
          faIcons: { type: Object },
          property: { type: String }
        };

        constructor() {
          super();
          this.iconPicker = {}
        }

        createRenderRoot() {
          return this;
        }

        render() {
          if (!this.element) {
            return nothing;
          }
          return html`
            <div class="col-xs-6 col-6">
              <label class="t3js-formengine-label">
                ${this.label}
                <a href="https://fontawesome.com/v4.7.0/icons/" target="_blank" title="FontAwesome 4.7 Icons"><i class="fa fa-question-circle"></i></a>
              </label>
              <div class="t3js-formengine-field-item icon-field">
                <div class="form-control-wrap">
                  <select></select>
                </div>
              </div>
            </div>
          `;
        }

        firstUpdated() {
          if (!this.element) {
            return;
          }
          const iconPicker = $(this.renderRoot.querySelector('select')).fontIconPicker({
            source: this.faIcons
          });
          iconPicker.setIcon(this.element[this.property]);
          this.iconPicker = $(iconPicker[0]).data('fontIconPicker');
        }
      }

      window.customElements.define('mask-font-icon-picker', FontIconPickerElement);

      return {FontIconPickerElement};
    }
);
