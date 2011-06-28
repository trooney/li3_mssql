CREATE TABLE [dbo].[companies](
	[id] [int] IDENTITY(1, 1) NOT NULL,
	[name] [varchar](255) NULL,
	[active] [tinyint] NULL,
	[created] [datetime] NULL,
	[modified] [datetime] NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]
