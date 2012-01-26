CREATE TABLE [dbo].[images](
	[id] [int]  IDENTITY(1,1) NOT NULL,
	[gallery_id] [int] NULL,
	[image] [varchar](max) NULL,
	[title] [varchar](50) NULL,
PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY];
