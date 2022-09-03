define([
      'lit',
      'TYPO3/CMS/Backend/ColorPicker',
      'jquery'
    ],
    function ({html, LitElement, nothing}, ColorPicker, $) {
      class ElementColorPickerElement extends LitElement {
        static properties = {
          element: { type: Object, attribute: false },
          label: { type: String },
          property: { type: String }
        };

        createRenderRoot() {
          return this;
        }

        render() {
          if (!this.element) {
            return nothing;
          }
          return html`
            <div class="col-xs-6 col-6">
                <label class="t3js-formengine-label" for="meta_color">
                    ${this.label}
                </label>
                <div class="t3js-formengine-field-item">
                    <div class="form-control-wrap">
                        <input
                            class="form-control t3js-color-picker"
                            .value=${this.element[this.property]}
                        />
                    </div>
                    <input type="hidden"/>
                </div>
            </div>
          `;
        }

        firstUpdated() {
          if (!this.element) {
            return;
          }
          ColorPicker.initialize();
          const $picker = $(this.renderRoot.querySelector('.t3js-color-picker'))
          $picker.minicolors('settings', {
            changeDelay: 200,
            change: function () {
              this.element[this.property] = $picker.data('minicolorsLastChange')['value'];
            }.bind(this)
          });
        }
      }

      window.customElements.define('mask-element-color-picker', ElementColorPickerElement);

      return {ElementColorPickerElement};
    }
);
