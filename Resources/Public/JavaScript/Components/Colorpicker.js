define([
      'lit',
      'TYPO3/CMS/Backend/ColorPicker',
      'jquery'
    ],
    function ({html, LitElement, nothing}, ColorPicker, $) {
      class ColorPickerElement extends LitElement {
        static properties = {
          global: { type: Object, attribute: false },
          tcakey: { type: String },
        };

        createRenderRoot() {
          return this;
        }

        render() {
          if (!this.global) {
            return nothing;
          }
          return html`
            <div class="form-control-wrap">
              <input
                  class="form-control t3js-color-picker"
                  .value=${this.global.activeField.tca[this.tcakey]}
              />
              <input type="hidden"/>
            </div>
          `;
        }

        firstUpdated() {
          if (!this.global) {
            return;
          }
          ColorPicker.initialize();
          const $picker = $(this.renderRoot.querySelector('.t3js-color-picker'))
          $picker.minicolors('settings', {
            changeDelay: 200,
            change: function () {
              this.global.activeField.tca[this.tcakey] = $picker.data('minicolorsLastChange')['value'];
            }.bind(this)
          });
        }
      }

      window.customElements.define('mask-color-picker', ColorPickerElement);

      return {ColorPickerElement};
    }
);
