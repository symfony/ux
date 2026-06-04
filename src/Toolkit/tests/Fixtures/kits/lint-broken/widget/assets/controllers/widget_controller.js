import { Controller } from '@hotwired/stimulus';
import _ from 'lodash';

export default class extends Controller {
    connect() {
        _.noop();
    }
}
