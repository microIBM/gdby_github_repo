/*!
 * brace-expand-join | MIT (c) Shinnosuke Watanabe
 * https://github.com/shinnn/node-brace-expand-join
*/

'use strict';

var path = require('path');

var arrayUniq = require('array-uniq');
var braceExpand = require('minimatch').braceExpand;

module.exports = function braceExpandJoin() {
  if (arguments.length === 0) {
    throw new Error('More than 1 glob pattern string required.');
  }

  var joinedPatterns = [].slice.call(arguments)
  .map(function(pattern) {
    return braceExpand(pattern).map(function(pattern) {
      return path.normalize(pattern);
    });
  })
  .reduce(function(parentPatterns, childPatterns) {
    return parentPatterns.reduce(function(ret, parentPattern) {
      return ret.concat(childPatterns.map(function(childPattern) {
        return path.join(parentPattern, childPattern);
      }));
    }, []);
  });

  joinedPatterns = arrayUniq(joinedPatterns);

  if (joinedPatterns.length > 1) {
    return '{' + joinedPatterns.join(',') + '}';
  }

  return joinedPatterns[0];
};
