-- The site's own traffic counts (lib/traffic.inc.php, admin/traffic.php), from every node into one place

-- Page views and unique visitors (and what people did: kind 'event') by hour, day and month
CREATE TABLE traffic_counts (
  kind varchar(16) NOT NULL,
  item varchar(128) NOT NULL,
  span varchar(8) NOT NULL,
  period timestamp(0) NOT NULL,
  pageviews bigint NOT NULL DEFAULT 0,
  visitors bigint NOT NULL DEFAULT 0,
  PRIMARY KEY (kind, item, span, period)
);

-- Who has been counted in a period already (a salted hash, not who they are), deleted once the period is over
CREATE TABLE traffic_visitors (
  kind varchar(16) NOT NULL,
  item varchar(128) NOT NULL,
  span varchar(8) NOT NULL,
  period timestamp(0) NOT NULL,
  visitor char(20) NOT NULL,
  PRIMARY KEY (kind, item, span, period, visitor)
);
CREATE INDEX traffic_visitors_span_period ON traffic_visitors (span, period);
