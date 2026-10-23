import test from 'node:test';
import assert from 'node:assert/strict';
import { tooltipPosition } from '../../resources/js/tooltips.js';
const viewport = { width: 1440, height: 900 };
test('tooltip is centered above a normal control', () => {
    assert.deepEqual(tooltipPosition({left: 400, top: 300, bottom: 340, width: 40}, {width: 100, height: 30}, viewport), {left: 370, top: 261});
});
test('header tooltip flips below and stays inside the left edge', () => {
    assert.deepEqual(tooltipPosition({left: 0, top: 5, bottom: 45, width: 40}, {width: 180, height: 40}, viewport), {left: 12, top: 54});
});
test('tooltip clamps to the right edge on icon buttons', () => {
    const point = tooltipPosition({left: 1400, top: 400, bottom: 440, width: 40}, {width: 180, height: 40}, viewport);
    assert.equal(point.left, 1248);
});
test('small viewport bottom tooltip remains on screen', () => {
    const point = tooltipPosition({left: 20, top: 30, bottom: 70, width: 30}, {width: 220, height: 100}, {width: 260, height: 180});
    assert.ok(point.top >= 12 && point.top + 100 <= 168);
    assert.ok(point.left >= 12 && point.left + 220 <= 248);
});
