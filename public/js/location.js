"use strict";
(self["webpackChunk"] = self["webpackChunk"] || []).push([["/js/location"],{

/***/ "./resources/js/helper.js":
/*!********************************!*\
  !*** ./resources/js/helper.js ***!
  \********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
var helper = {
  getSiteUrl: function getSiteUrl() {
    return window.location.origin;
  },
  validateEmail: function validateEmail(email) {
    var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  },
  validateDomain: function validateDomain(domain) {
    var re = /^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9](?:\.[a-zA-Z]{2,})+$/;
    return re.test(String(domain).toLowerCase());
  },
  alertError: function alertError(msg) {
    $('#errorModalContent').html(msg);
    $('#errorModal').modal('show');
  },
  confirm: function confirm(msg, yesCallback, cancelCallback) {
    if (msg) {
      $('#confirmModalContent').html(msg);
    }

    $('#confirmModal').modal('show');
    $('#confirmBtnOk').on('click', function () {
      $('#confirmModal').modal('hide');
      yesCallback();
    });
    $('#confirmBtnCancel').on('click', function () {
      $('#confirmModal').modal('hide');
      cancelCallback();
    });
  },
  roundToHalf: function roundToHalf(num) {
    return Math.round(num * 2) / 2;
  },
  reloadPage: function reloadPage() {
    window.location.reload();
  },
  serverErrorMessage: function serverErrorMessage() {
    return 'An error occured, please try again or contact support if the problem persists.';
  },
  inchesToFeet: function inchesToFeet(num) {
    return num / 12;
  },
  feetToInches: function feetToInches(num) {
    return num * 12;
  },
  getDecimal: function getDecimal(num) {
    return num % 1;
  },
  isMobilePhone: function isMobilePhone() {
    return window.matchMedia("only screen and (max-width: 800px)").matches;
  },
  redirectTo: function redirectTo(url) {
    window.location.href = url;
  }
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (helper);

/***/ }),

/***/ "./resources/js/location.js":
/*!**********************************!*\
  !*** ./resources/js/location.js ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _helper__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./helper */ "./resources/js/helper.js");

var region = {
  init: function init() {
    this.initDatatable();
    this["delete"]();
  },
  initDatatable: function initDatatable() {
    $('#regionsTable').DataTable({
      responsive: true,
      order: [[0, 'asc']],
      language: {
        search: '',
        searchPlaceholder: "Search..."
      }
    });
  },
  "delete": function _delete() {
    $('body').on('click', '.deleteRegionBtn', function (e) {
      var self = $(e.target);
      var regionId = self.data('id');
      var form = $("#deleteRegionForm".concat(regionId));
      _helper__WEBPACK_IMPORTED_MODULE_0__["default"].confirm('', function () {
        form.trigger('submit');
      }, function () {});
    });
  }
};
$(function () {
  region.init();
});

/***/ })

},
/******/ __webpack_require__ => { // webpackRuntimeModules
/******/ var __webpack_exec__ = (moduleId) => (__webpack_require__(__webpack_require__.s = moduleId))
/******/ var __webpack_exports__ = (__webpack_exec__("./resources/js/location.js"));
/******/ }
]);